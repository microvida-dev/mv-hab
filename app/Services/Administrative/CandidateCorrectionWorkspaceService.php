<?php

namespace App\Services\Administrative;

use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionRequiredAction;
use App\Enums\CorrectionResponseKind;
use App\Enums\CorrectionResponseStatus;
use App\Enums\DocumentStatus;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestItem;
use App\Models\CorrectionResponse;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Documents\DocumentUploadService;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CandidateCorrectionWorkspaceService
{
    public function __construct(
        private readonly DocumentUploadService $documents,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(
        CorrectionRequest $request,
        CorrectionRequestItem $item,
        array $data,
        ?UploadedFile $file,
        User $candidate,
    ): CorrectionResponse {
        if ($request->isLegacy()) {
            throw ValidationException::withMessages([
                'correction_request' => 'Este pedido utiliza o fluxo legacy de resposta.',
            ]);
        }

        if (
            (int) $request->user_id !== (int) $candidate->id
            || ! $request->isResponseWindowOpen()
        ) {
            throw ValidationException::withMessages([
                'correction_request' => 'Este pedido não aceita alterações neste momento.',
            ]);
        }

        return DB::transaction(function () use (
            $request,
            $item,
            $data,
            $file,
            $candidate,
        ): CorrectionResponse {
            $lockedRequest = CorrectionRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) $lockedRequest->user_id !== (int) $candidate->id
                || ! $lockedRequest->isResponseWindowOpen()
            ) {
                throw ValidationException::withMessages([
                    'correction_request' => 'O prazo ou o estado do pedido já não permite alterações.',
                ]);
            }

            $lockedItem = $lockedRequest->items()
                ->whereKey($item->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedItem instanceof CorrectionRequestItem) {
                throw ValidationException::withMessages([
                    'correction_request_item_id' => 'O elemento não pertence a este pedido.',
                ]);
            }

            if (in_array($lockedItem->status, [
                CorrectionRequestItemStatus::Accepted,
                CorrectionRequestItemStatus::Waived,
                CorrectionRequestItemStatus::Cancelled,
            ], true)) {
                throw ValidationException::withMessages([
                    'correction_request_item_id' => 'Este elemento já não pode ser alterado.',
                ]);
            }

            $response = CorrectionResponse::query()
                ->withTrashed()
                ->where('correction_request_id', $lockedRequest->id)
                ->where('correction_request_item_id', $lockedItem->id)
                ->where('user_id', $candidate->id)
                ->lockForUpdate()
                ->first();

            if ($response instanceof CorrectionResponse && $response->trashed()) {
                $response->restore();
            }

            $response ??= new CorrectionResponse;
            [$kind, $text, $submission, $version] = $this->prepareAnswer(
                request: $lockedRequest,
                item: $lockedItem,
                response: $response,
                data: $data,
                file: $file,
                candidate: $candidate,
            );

            $response->forceFill([
                'correction_request_id' => $lockedRequest->id,
                'correction_request_item_id' => $lockedItem->id,
                'application_id' => $lockedRequest->application_id,
                'user_id' => $candidate->id,
                'response_text' => $text,
                'response_kind' => $kind,
                'document_submission_id' => $submission?->id,
                'document_version_id' => $version?->id,
                'prepared_at' => now(),
                'submitted_at' => null,
                'status' => CorrectionResponseStatus::Draft,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_result' => null,
                'review_notes' => null,
            ])->save();

            $lockedItem->forceFill([
                'status' => CorrectionRequestItemStatus::Responded,
            ])->save();

            $this->refreshRequestProgress($lockedRequest);

            $this->audit->record(
                event: AuditEvents::UPDATE,
                auditable: $response,
                module: 'administrative_processes',
                action: 'candidate_correction_item_prepared',
                description: 'Elemento do pedido de aperfeiçoamento preparado pelo candidato.',
                newValues: [
                    'response_kind' => $kind->value,
                    'document_submission_id' => $submission?->id,
                    'document_version_id' => $version?->id,
                    'status' => CorrectionResponseStatus::Draft->value,
                ],
                metadata: [
                    'actor_id' => $candidate->id,
                    'correction_request_id' => $lockedRequest->id,
                    'correction_request_item_id' => $lockedItem->id,
                    'municipal_notification_dispatched' => false,
                ],
            );

            return $response->refresh()->load([
                'correctionRequestItem',
                'documentSubmission.currentVersion',
                'documentVersion.replacedVersion',
            ]);
        }, 3);
    }

    /**
     * @return array{
     *     total: int,
     *     completed: int,
     *     pending: int,
     *     percentage: int,
     *     ready_for_submission: bool
     * }
     */
    public function progress(CorrectionRequest $request): array
    {
        $query = $request->items()
            ->where('is_required', true)
            ->whereNotIn('status', [
                CorrectionRequestItemStatus::Cancelled->value,
            ]);

        $total = (clone $query)->count();
        $completed = (clone $query)
            ->whereIn('status', [
                CorrectionRequestItemStatus::Responded->value,
                CorrectionRequestItemStatus::Accepted->value,
                CorrectionRequestItemStatus::Waived->value,
            ])
            ->count();
        $pending = max(0, $total - $completed);

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'percentage' => $total === 0
                ? 100
                : (int) round(($completed / $total) * 100),
            'ready_for_submission' => $total > 0 && $pending === 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     CorrectionResponseKind,
     *     string|null,
     *     DocumentSubmission|null,
     *     DocumentVersion|null
     * }
     */
    private function prepareAnswer(
        CorrectionRequest $request,
        CorrectionRequestItem $item,
        CorrectionResponse $response,
        array $data,
        ?UploadedFile $file,
        User $candidate,
    ): array {
        $documentAction = in_array($item->required_action, [
            CorrectionRequiredAction::UploadDocument,
            CorrectionRequiredAction::ReplaceDocument,
        ], true);
        $justification = $this->nullableText($data['justification'] ?? null);
        $responseText = $this->nullableText($data['response_text'] ?? null);

        if ($documentAction) {
            if ($file instanceof UploadedFile && $justification !== null) {
                throw ValidationException::withMessages([
                    'file' => 'Submeta o documento ou apresente uma justificação, não ambos.',
                ]);
            }

            if ($file instanceof UploadedFile) {
                $submission = $this->storeDocument(
                    request: $request,
                    item: $item,
                    existingResponse: $response,
                    data: $data,
                    file: $file,
                    candidate: $candidate,
                );
                $version = $submission->currentVersion;

                if (! $version instanceof DocumentVersion) {
                    throw ValidationException::withMessages([
                        'file' => 'Não foi possível confirmar a versão documental criada.',
                    ]);
                }

                return [
                    CorrectionResponseKind::Document,
                    null,
                    $submission,
                    $version,
                ];
            }

            if ($justification === null) {
                throw ValidationException::withMessages([
                    'justification' => 'Submeta o documento solicitado ou explique por que motivo não o consegue apresentar.',
                ]);
            }

            if ($response->document_submission_id !== null) {
                throw ValidationException::withMessages([
                    'justification' => 'Já existe um documento preparado para este elemento. Substitua-o por uma nova versão.',
                ]);
            }

            return [
                CorrectionResponseKind::Justification,
                $justification,
                null,
                null,
            ];
        }

        if ($file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'file' => 'Este elemento não solicita um documento.',
            ]);
        }

        if ($responseText === null) {
            throw ValidationException::withMessages([
                'response_text' => 'Preencha o esclarecimento solicitado.',
            ]);
        }

        return [
            CorrectionResponseKind::Explanation,
            $responseText,
            null,
            null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeDocument(
        CorrectionRequest $request,
        CorrectionRequestItem $item,
        CorrectionResponse $existingResponse,
        array $data,
        UploadedFile $file,
        User $candidate,
    ): DocumentSubmission {
        $application = $request->application()->first();

        if (! $application instanceof Application) {
            throw ValidationException::withMessages([
                'application' => 'O pedido não possui uma candidatura válida.',
            ]);
        }

        $registration = $application->adhesionRegistration()->first();

        if (! $registration instanceof AdhesionRegistration) {
            throw ValidationException::withMessages([
                'document' => 'A candidatura não possui um Registo de Adesão válido.',
            ]);
        }

        if (
            $item->required_document_id === null
            || $item->document_type_id === null
        ) {
            throw ValidationException::withMessages([
                'document' => 'O elemento publicado não identifica o requisito documental.',
            ]);
        }

        $documentData = array_filter([
            'required_document_id' => $item->required_document_id,
            'document_type_id' => $item->document_type_id,
            'requirement_instance' => max(1, $item->requirement_instance),
            'application_public_id' => $application->public_id,
            'reference_period' => $data['reference_period'] ?? null,
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'notes' => 'Documento preparado no pedido '.$request->request_number.'.',
            ...$this->targetData($item),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $existingSubmission = $existingResponse->documentSubmission()->first();

        if ($existingSubmission instanceof DocumentSubmission) {
            $this->assertDocumentInScope(
                $existingSubmission,
                $request,
                $item,
                $candidate,
            );

            return $this->documents->replace(
                submission: $existingSubmission,
                file: $file,
                data: $documentData,
                actor: $candidate,
                allowCorrection: true,
            );
        }

        if ($item->required_action === CorrectionRequiredAction::ReplaceDocument) {
            $source = $this->sourceDocument($request, $item, $candidate);

            return $this->documents->replace(
                submission: $source,
                file: $file,
                data: $documentData,
                actor: $candidate,
                allowCorrection: true,
            );
        }

        return $this->documents->store(
            registration: $registration,
            file: $file,
            data: $documentData,
            actor: $candidate,
            allowCorrection: true,
        );
    }

    private function sourceDocument(
        CorrectionRequest $request,
        CorrectionRequestItem $item,
        User $candidate,
    ): DocumentSubmission {
        $source = $item->sourceDocumentSubmission()->first();

        if (! $source instanceof DocumentSubmission) {
            $source = DocumentSubmission::query()
                ->where('user_id', $candidate->id)
                ->where('application_id', $request->application_id)
                ->where('required_document_id', $item->required_document_id)
                ->where('requirement_instance', $item->requirement_instance)
                ->whereIn('status', [
                    DocumentStatus::Rejected->value,
                    DocumentStatus::Expired->value,
                ])
                ->latest('updated_at')
                ->latest('id')
                ->first();
        }

        if (! $source instanceof DocumentSubmission) {
            throw ValidationException::withMessages([
                'file' => 'O documento original a substituir não foi encontrado.',
            ]);
        }

        $this->assertDocumentInScope($source, $request, $item, $candidate);

        if ($source->status === DocumentStatus::Validated) {
            throw ValidationException::withMessages([
                'file' => 'Um documento validado não pode ser novamente solicitado.',
            ]);
        }

        return $source;
    }

    private function assertDocumentInScope(
        DocumentSubmission $submission,
        CorrectionRequest $request,
        CorrectionRequestItem $item,
        User $candidate,
    ): void {
        $targetMatches = match ($item->target_type) {
            (new AdhesionRegistration)->getMorphClass() => (int) $submission->adhesion_registration_id === (int) $item->target_id,
            (new Household)->getMorphClass() => (int) $submission->household_id === (int) $item->target_id,
            (new HouseholdMember)->getMorphClass() => (int) $submission->household_member_id === (int) $item->target_id,
            (new IncomeRecord)->getMorphClass() => (int) $submission->income_record_id === (int) $item->target_id,
            (new CurrentHousingSituation)->getMorphClass() => (int) $submission->current_housing_situation_id === (int) $item->target_id,
            (new Application)->getMorphClass() => (int) $submission->application_id === (int) $item->target_id,
            default => false,
        };

        if (
            (int) $submission->user_id !== (int) $candidate->id
            || (int) $submission->application_id !== (int) $request->application_id
            || (int) $submission->required_document_id !== (int) $item->required_document_id
            || (int) $submission->document_type_id !== (int) $item->document_type_id
            || (int) $submission->requirement_instance !== (int) $item->requirement_instance
            || ! $targetMatches
        ) {
            throw ValidationException::withMessages([
                'file' => 'O documento não pertence ao elemento solicitado neste pedido.',
            ]);
        }
    }

    /** @return array<string, int> */
    private function targetData(CorrectionRequestItem $item): array
    {
        $target = $item->target()->first();

        if (! $target instanceof Model) {
            throw ValidationException::withMessages([
                'correction_request_item_id' => 'O alvo do elemento solicitado deixou de estar disponível.',
            ]);
        }

        return match (true) {
            $target instanceof HouseholdMember => [
                'household_member_id' => (int) $target->getKey(),
            ],
            $target instanceof IncomeRecord => [
                'income_record_id' => (int) $target->getKey(),
            ],
            $target instanceof CurrentHousingSituation => [
                'current_housing_situation_id' => (int) $target->getKey(),
            ],
            default => [],
        };
    }

    private function refreshRequestProgress(CorrectionRequest $request): void
    {
        $completed = $request->items()
            ->where('is_required', true)
            ->whereIn('status', [
                CorrectionRequestItemStatus::Responded->value,
                CorrectionRequestItemStatus::Accepted->value,
                CorrectionRequestItemStatus::Waived->value,
            ])
            ->count();

        $request->forceFill([
            'status' => $completed > 0
                ? CorrectionRequestStatus::PartiallyCompleted
                : CorrectionRequestStatus::Open,
            'responded_at' => null,
            'submitted_at' => null,
        ])->save();
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
