<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseKind;
use App\Enums\CorrectionResponseStatus;
use App\Enums\OfficialNotificationChannel;
use App\Enums\OfficialNotificationType;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestItem;
use App\Models\CorrectionResponse;
use App\Models\CorrectionSubmissionReceipt;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\OfficialNotificationService;
use App\Services\Support\CanonicalJsonHasher;
use App\Support\AuditEvents;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CorrectionSubmissionService
{
    public function __construct(
        private readonly AdministrativeWorkflowTransitionService $transitions,
        private readonly OfficialNotificationService $notifications,
        private readonly CanonicalJsonHasher $hasher,
        private readonly AuditLogger $audit,
    ) {}

    public function submit(
        CorrectionRequest $request,
        User $candidate,
    ): CorrectionSubmissionReceipt {
        /**
         * @var array{
         *     receipt: CorrectionSubmissionReceipt|null,
         *     created: bool,
         *     expired: bool
         * } $result
         */
        $result = DB::transaction(function () use (
            $request,
            $candidate,
        ): array {
            $lockedRequest = CorrectionRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedRequest->isLegacy()
                || (int) $lockedRequest->user_id
                    !== (int) $candidate->id
            ) {
                throw ValidationException::withMessages([
                    'correction_request' => 'O pedido não admite esta submissão formal.',
                ]);
            }

            $existing = CorrectionSubmissionReceipt::query()
                ->where(
                    'correction_request_id',
                    $lockedRequest->id,
                )
                ->lockForUpdate()
                ->first();

            if ($existing instanceof CorrectionSubmissionReceipt) {
                return [
                    'receipt' => $existing,
                    'created' => false,
                    'expired' => false,
                ];
            }

            $submittedAt = now('UTC');

            if (
                ! $lockedRequest->isVisibleToCandidate($submittedAt)
                || (
                    $lockedRequest->opened_at !== null
                    && $submittedAt->lessThan(
                        $lockedRequest->opened_at,
                    )
                )
            ) {
                throw ValidationException::withMessages([
                    'correction_request' => 'O pedido ainda não se encontra disponível para submissão.',
                ]);
            }

            if (
                $lockedRequest->response_deadline_at === null
                || $submittedAt->greaterThan(
                    $lockedRequest->response_deadline_at,
                )
            ) {
                $this->expireLocked(
                    $lockedRequest,
                    $submittedAt,
                );

                return [
                    'receipt' => null,
                    'created' => false,
                    'expired' => true,
                ];
            }

            if (! $lockedRequest->status->acceptsCandidateWork()) {
                throw ValidationException::withMessages([
                    'correction_request' => 'O estado atual do pedido não permite submissão.',
                ]);
            }

            /** @var Collection<int, CorrectionRequestItem> $items */
            $items = $lockedRequest->items()
                ->whereNotIn('status', [
                    CorrectionRequestItemStatus::Cancelled->value,
                ])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if (
                $items->where('is_required', true)->isEmpty()
            ) {
                throw ValidationException::withMessages([
                    'items' => 'O pedido não contém elementos obrigatórios para submeter.',
                ]);
            }

            /** @var Collection<int, CorrectionResponse> $responses */
            $responses = CorrectionResponse::query()
                ->where(
                    'correction_request_id',
                    $lockedRequest->id,
                )
                ->where('user_id', $candidate->id)
                ->with([
                    'documentSubmission.currentVersion',
                    'documentVersion',
                ])
                ->orderBy('correction_request_item_id')
                ->lockForUpdate()
                ->get();

            $responsesByItem = $responses->keyBy(
                'correction_request_item_id',
            );

            foreach ($items as $item) {
                if (! $item->is_required) {
                    continue;
                }

                if (in_array($item->status, [
                    CorrectionRequestItemStatus::Accepted,
                    CorrectionRequestItemStatus::Waived,
                ], true)) {
                    continue;
                }

                $response = $responsesByItem->get($item->id);

                if (
                    ! $response instanceof CorrectionResponse
                    || $item->status
                        !== CorrectionRequestItemStatus::Responded
                ) {
                    throw ValidationException::withMessages([
                        'items' => 'Prepare todos os elementos obrigatórios antes da submissão formal.',
                    ]);
                }

                $this->assertPreparedResponse($response);
            }

            foreach ($responses as $response) {
                if (
                    $response->status
                    !== CorrectionResponseStatus::Draft
                ) {
                    throw ValidationException::withMessages([
                        'responses' => 'Uma resposta preparada possui um estado incompatível com a submissão formal.',
                    ]);
                }

                $this->assertPreparedResponse($response);
            }

            $application = Application::query()
                ->whereKey($lockedRequest->application_id)
                ->lockForUpdate()
                ->firstOrFail();
            $process = AdministrativeProcess::query()
                ->whereKey(
                    $lockedRequest->administrative_process_id,
                )
                ->lockForUpdate()
                ->firstOrFail();

            $process = $this->alignProcessForCandidateResponse(
                $process,
                $candidate,
            );

            if (
                $process->status
                !== AdministrativeProcessStatus::AwaitingCandidateResponse
            ) {
                throw ValidationException::withMessages([
                    'process' => 'O processo administrativo não se encontra a aguardar a resposta do candidato.',
                ]);
            }

            $receiptNumber = 'REC-'.$lockedRequest->request_number;
            $snapshot = $this->snapshot(
                request: $lockedRequest,
                application: $application,
                items: $items,
                responses: $responses,
                receiptNumber: $receiptNumber,
                submittedAt: $submittedAt,
            );
            $snapshotHash = $this->hasher->hash($snapshot);

            foreach ($responses as $response) {
                $response->forceFill([
                    'status' => CorrectionResponseStatus::Submitted,
                    'submitted_at' => $submittedAt,
                ])->save();
            }

            $lockedRequest->forceFill([
                'status' => CorrectionRequestStatus::Submitted,
                'responded_at' => $submittedAt,
                'submitted_at' => $submittedAt,
            ])->save();

            $process = $this->transitions->transition(
                $process,
                AdministrativeProcessStatus::CorrectionSubmitted,
                $candidate,
                'Submissão formal do aperfeiçoamento pelo candidato.',
            );

            $recipient = $this->municipalRecipient(
                $lockedRequest,
                $process,
                $candidate,
            );
            $notification = $this->notifications->createInternal(
                user: $recipient,
                type: OfficialNotificationType::CorrectionSubmissionReceived,
                subject: 'Aperfeiçoamento submetido',
                body: 'O candidato submeteu formalmente o pedido '
                    .$lockedRequest->request_number
                    .'. O recibo é '.$receiptNumber.'.',
                notifiable: $lockedRequest,
                application: $application,
                actor: $candidate,
                channel: OfficialNotificationChannel::Backoffice,
                requiresAcknowledgement: false,
                actionUrl: route(
                    'backoffice.correction-requests.show',
                    $lockedRequest,
                ),
                enforceMandatoryEmail: false,
            );

            $receipt = new CorrectionSubmissionReceipt;
            $receipt->forceFill([
                'correction_request_id' => $lockedRequest->id,
                'application_id' => $application->id,
                'user_id' => $candidate->id,
                'municipal_notification_id' => $notification->id,
                'receipt_number' => $receiptNumber,
                'snapshot_payload' => $snapshot,
                'snapshot_hash' => $snapshotHash,
                'submitted_at' => $submittedAt,
                'created_at' => $submittedAt,
            ])->save();

            $this->audit->record(
                event: AuditEvents::CREATE,
                auditable: $receipt,
                module: 'administrative_processes',
                action: 'candidate_correction_formally_submitted',
                description: 'Pedido de aperfeiçoamento submetido formalmente pelo candidato.',
                newValues: [
                    'request_status' => CorrectionRequestStatus::Submitted->value,
                    'snapshot_hash' => $snapshotHash,
                ],
                metadata: [
                    'actor_id' => $candidate->id,
                    'correction_request_id' => $lockedRequest->id,
                    'receipt_id' => $receipt->id,
                    'municipal_notification_id' => $notification->id,
                    'response_count' => $responses->count(),
                ],
            );

            return [
                'receipt' => $receipt->refresh(),
                'created' => true,
                'expired' => false,
            ];
        }, 3);

        if ($result['expired']) {
            throw ValidationException::withMessages([
                'correction_request' => 'O prazo de submissão terminou. O pedido foi marcado como expirado.',
            ]);
        }

        $receipt = $result['receipt'];

        if (! $receipt instanceof CorrectionSubmissionReceipt) {
            throw ValidationException::withMessages([
                'correction_request' => 'Não foi possível emitir o recibo da submissão.',
            ]);
        }

        return $receipt;
    }

    private function assertPreparedResponse(
        CorrectionResponse $response,
    ): void {
        if (
            $response->prepared_at === null
            || $response->response_kind === null
        ) {
            throw ValidationException::withMessages([
                'responses' => 'Uma resposta não possui preparação formal válida.',
            ]);
        }

        if (
            $response->response_kind
            === CorrectionResponseKind::Document
        ) {
            $version = $response->documentVersion;

            if (
                $response->document_submission_id === null
                || ! $version instanceof DocumentVersion
                || (int) $version->document_submission_id
                    !== (int) $response->document_submission_id
                || trim((string) $version->checksum) === ''
            ) {
                throw ValidationException::withMessages([
                    'responses' => 'Uma resposta documental não possui versão imutável válida.',
                ]);
            }

            return;
        }

        if (trim((string) $response->response_text) === '') {
            throw ValidationException::withMessages([
                'responses' => 'Uma resposta textual encontra-se vazia.',
            ]);
        }

        if (
            $response->document_submission_id !== null
            || $response->document_version_id !== null
        ) {
            throw ValidationException::withMessages([
                'responses' => 'Uma resposta textual contém uma associação documental indevida.',
            ]);
        }
    }

    private function expireLocked(
        CorrectionRequest $request,
        CarbonInterface $expiredAt,
    ): void {
        $request->forceFill([
            'status' => CorrectionRequestStatus::Expired,
            'expired_at' => $expiredAt,
        ])->save();

        $process = AdministrativeProcess::query()
            ->whereKey($request->administrative_process_id)
            ->lockForUpdate()
            ->firstOrFail();

        if (
            $process->status
            === AdministrativeProcessStatus::AwaitingCandidateResponse
        ) {
            $this->transitions->transition(
                $process,
                AdministrativeProcessStatus::CorrectionOverdue,
                null,
                'Prazo de resposta ao pedido de aperfeiçoamento vencido.',
            );
        }

        $this->audit->record(
            event: AuditEvents::UPDATE,
            auditable: $request,
            module: 'administrative_processes',
            action: 'correction_request_expired_on_submission',
            description: 'Tentativa de submissão após o prazo marcou o pedido como expirado.',
            metadata: [
                'system_initiated' => true,
            ],
        );
    }

    private function alignProcessForCandidateResponse(
        AdministrativeProcess $process,
        User $actor,
    ): AdministrativeProcess {
        $path = match ($process->status) {
            AdministrativeProcessStatus::DocumentReview => [
                AdministrativeProcessStatus::EligibilityReview,
                AdministrativeProcessStatus::RequiresCorrection,
                AdministrativeProcessStatus::AwaitingCandidateResponse,
            ],
            AdministrativeProcessStatus::EligibilityReview => [
                AdministrativeProcessStatus::RequiresCorrection,
                AdministrativeProcessStatus::AwaitingCandidateResponse,
            ],
            AdministrativeProcessStatus::RequiresCorrection => [
                AdministrativeProcessStatus::AwaitingCandidateResponse,
            ],
            AdministrativeProcessStatus::AwaitingCandidateResponse => [],
            default => null,
        };

        if ($path === null) {
            return $process;
        }

        foreach ($path as $status) {
            $process = $this->transitions->transition(
                $process,
                $status,
                $actor,
                'Alinhamento do processo com o pedido de aperfeiçoamento publicado.',
            );
        }

        return $process;
    }

    private function municipalRecipient(
        CorrectionRequest $request,
        AdministrativeProcess $process,
        User $candidate,
    ): User {
        $recipientId = $process->assigned_to
            ?? $request->issued_by;

        $recipient = $recipientId !== null
            ? User::query()
                ->whereKey($recipientId)
                ->lockForUpdate()
                ->first()
            : null;

        if (
            ! $recipient instanceof User
            || (int) $recipient->id === (int) $candidate->id
            || $recipient->hasRole('candidate')
        ) {
            throw ValidationException::withMessages([
                'notification' => 'Não foi possível determinar um destinatário municipal para a submissão.',
            ]);
        }

        return $recipient;
    }

    /**
     * @param  Collection<int, CorrectionRequestItem>  $items
     * @param  Collection<int, CorrectionResponse>  $responses
     * @return array<string, mixed>
     */
    private function snapshot(
        CorrectionRequest $request,
        Application $application,
        Collection $items,
        Collection $responses,
        string $receiptNumber,
        CarbonInterface $submittedAt,
    ): array {
        $responsesByItem = $responses->keyBy(
            'correction_request_item_id',
        );
        $extensions = $request->deadlineExtensions()
            ->orderBy('authorized_at')
            ->orderBy('id')
            ->get();

        return [
            'schema_version' => 1,
            'receipt_number' => $receiptNumber,
            'request' => [
                'id' => $request->id,
                'number' => $request->request_number,
                'source_snapshot_hash' => $request->source_snapshot_hash,
                'original_deadline_at' => (
                    $request->original_response_deadline_at
                    ?? $request->response_deadline_at
                )?->toIso8601String(),
                'effective_deadline_at' => $request->response_deadline_at?->toIso8601String(),
                'extension_count' => (int) $request->deadline_extension_count,
            ],
            'application' => [
                'id' => $application->id,
                'public_id' => $application->public_id,
                'number' => $application->application_number,
            ],
            'candidate' => [
                'id' => $request->user_id,
            ],
            'submission' => [
                'submitted_at' => $submittedAt->toIso8601String(),
                'response_count' => $responses->count(),
            ],
            'extensions' => $extensions
                ->map(static fn (
                    $extension,
                ): array => [
                    'id' => $extension->id,
                    'previous_deadline_at' => $extension->previous_deadline_at
                        ->toIso8601String(),
                    'extended_deadline_at' => $extension->extended_deadline_at
                        ->toIso8601String(),
                    'authorized_by' => $extension->authorized_by,
                    'authorized_at' => $extension->authorized_at
                        ->toIso8601String(),
                    'reason' => $extension->reason,
                ])
                ->values()
                ->all(),
            'items' => $items
                ->map(function (
                    CorrectionRequestItem $item,
                ) use ($responsesByItem): array {
                    $response = $responsesByItem->get($item->id);
                    $version = $response
                        instanceof CorrectionResponse
                        ? $response->documentVersion
                        : null;

                    return [
                        'item_id' => $item->id,
                        'title' => $item->title,
                        'is_required' => $item->is_required,
                        'issue_type' => $item->issue_type->value,
                        'required_action' => $item->required_action->value,
                        'status' => $item->status->value,
                        'required_document_id' => $item->required_document_id,
                        'requirement_instance' => $item->requirement_instance,
                        'response' => $response instanceof CorrectionResponse
                            ? [
                                'response_id' => $response->id,
                                'kind' => $response->response_kind?->value,
                                'text' => $response->response_text,
                                'prepared_at' => $response->prepared_at
                                    ?->toIso8601String(),
                                'document_submission_id' => $response
                                    ->document_submission_id,
                                'document_version' => $version instanceof DocumentVersion
                                    ? [
                                        'id' => $version->id,
                                        'replaces_document_version_id' => $version
                                            ->replaces_document_version_id,
                                        'version_number' => $version->version_number,
                                        'original_filename' => $version->original_filename,
                                        'mime_type' => $version->mime_type,
                                        'file_size' => $version->file_size,
                                        'checksum' => $version->checksum,
                                    ]
                                    : null,
                            ]
                            : null,
                    ];
                })
                ->values()
                ->all(),
        ];
    }
}
