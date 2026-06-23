<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionResponseStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestItem;
use App\Models\CorrectionResponse;
use App\Models\DocumentSubmission;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CorrectionResponseService
{
    public function __construct(
        private readonly AdministrativeWorkflowTransitionService $transitionService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(CorrectionRequest $request, array $data, User $candidate): CorrectionResponse
    {
        if ($request->user_id !== $candidate->id || ! $request->isOpenForCandidateResponse()) {
            throw ValidationException::withMessages(['correction_request' => 'Este pedido não aceita resposta neste momento.']);
        }

        $item = $this->requiredItem($request, (int) $data['correction_request_item_id']);
        $documentId = $data['document_submission_id'] ?? null;

        if ($documentId !== null) {
            $this->ensureDocumentBelongsToCandidate($documentId, $request, $candidate);
        }

        return DB::transaction(function () use ($request, $item, $data, $candidate, $documentId) {
            $response = CorrectionResponse::query()->firstOrNew([
                'correction_request_id' => $request->id,
                'correction_request_item_id' => $item->id,
                'user_id' => $candidate->id,
            ]);
            $response->fill([
                'response_text' => $data['response_text'] ?? null,
                'document_submission_id' => $documentId,
            ]);
            $response->forceFill([
                'correction_request_id' => $request->id,
                'correction_request_item_id' => $item->id,
                'application_id' => $request->application_id,
                'user_id' => $candidate->id,
                'submitted_at' => now(),
                'status' => CorrectionResponseStatus::Submitted,
            ])->save();

            $item->forceFill(['status' => CorrectionRequestItemStatus::Responded])->save();
            $this->refreshRequestResponseStatus($request);

            $process = $this->requiredAdministrativeProcess($request);

            if ($this->processStatus($process) === AdministrativeProcessStatus::AwaitingCandidateResponse) {
                $this->transitionService->transition(
                    $process,
                    AdministrativeProcessStatus::CorrectionSubmitted,
                    $candidate,
                    'Resposta do candidato ao pedido de aperfeiçoamento.',
                );
            }

            $this->auditLogger->record(
                event: AuditEvents::CREATE,
                auditable: $response,
                module: 'administrative_processes',
                action: 'correction_response_submit',
                description: 'Resposta ao pedido de aperfeiçoamento submetida pelo candidato.',
                metadata: ['document_linked' => $documentId !== null],
            );

            $response->refresh();

            return $response->load(['correctionRequestItem', 'documentSubmission']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function accept(CorrectionResponse $response, array $data, User $actor): CorrectionResponse
    {
        return $this->review($response, CorrectionResponseReviewResult::Accepted, $data['review_notes'] ?? null, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reject(CorrectionResponse $response, array $data, User $actor): CorrectionResponse
    {
        return $this->review($response, CorrectionResponseReviewResult::Rejected, $data['review_notes'] ?? null, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function requestMoreInformation(CorrectionResponse $response, array $data, User $actor): CorrectionResponse
    {
        return $this->review($response, CorrectionResponseReviewResult::RequiresMoreInformation, $data['review_notes'] ?? null, $actor);
    }

    private function review(
        CorrectionResponse $response,
        CorrectionResponseReviewResult $result,
        ?string $notes,
        User $actor,
    ): CorrectionResponse {
        return DB::transaction(function () use ($response, $result, $notes, $actor) {
            $status = $result === CorrectionResponseReviewResult::Accepted
                ? CorrectionResponseStatus::Accepted
                : CorrectionResponseStatus::Rejected;

            $response->forceFill([
                'status' => $status,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_result' => $result,
                'review_notes' => $notes,
            ])->save();

            $itemStatus = $result === CorrectionResponseReviewResult::Accepted
                ? CorrectionRequestItemStatus::Accepted
                : CorrectionRequestItemStatus::Rejected;
            $this->requiredCorrectionRequestItem($response)->forceFill(['status' => $itemStatus])->save();

            $request = $this->requiredCorrectionRequest($response);
            if ($this->allRequiredItemsAccepted($request)) {
                $request->forceFill([
                    'status' => CorrectionRequestStatus::Accepted,
                    'closed_at' => now(),
                ])->save();
            } else {
                $request->forceFill(['status' => CorrectionRequestStatus::UnderReview])->save();
            }

            $process = $this->requiredAdministrativeProcess($request);

            if ($this->processStatus($process) === AdministrativeProcessStatus::CorrectionSubmitted) {
                $this->transitionService->transition(
                    $process,
                    AdministrativeProcessStatus::CorrectionUnderReview,
                    $actor,
                    'Resposta ao aperfeiçoamento em análise técnica.',
                );
            }

            $this->auditLogger->record(
                event: $result === CorrectionResponseReviewResult::Accepted ? AuditEvents::APPROVE : AuditEvents::REJECT,
                auditable: $response,
                module: 'administrative_processes',
                action: 'correction_response_review',
                description: 'Resposta ao aperfeiçoamento analisada.',
                newValues: ['review_result' => $result->value],
            );

            return $response->refresh();
        });
    }

    private function refreshRequestResponseStatus(CorrectionRequest $request): void
    {
        $required = $request->items()->where('is_required', true)->count();
        $responded = $request->items()
            ->where('is_required', true)
            ->whereIn('status', [
                CorrectionRequestItemStatus::Responded->value,
                CorrectionRequestItemStatus::Accepted->value,
                CorrectionRequestItemStatus::Waived->value,
            ])
            ->count();

        $request->forceFill([
            'status' => $responded >= $required ? CorrectionRequestStatus::Responded : CorrectionRequestStatus::PartiallyResponded,
            'responded_at' => $responded >= $required ? now() : null,
        ])->save();
    }

    private function allRequiredItemsAccepted(CorrectionRequest $request): bool
    {
        return $request->items()
            ->where('is_required', true)
            ->whereNotIn('status', [
                CorrectionRequestItemStatus::Accepted->value,
                CorrectionRequestItemStatus::Waived->value,
            ])
            ->doesntExist();
    }

    private function ensureDocumentBelongsToCandidate(int $documentId, CorrectionRequest $request, User $candidate): void
    {
        $belongs = DocumentSubmission::query()
            ->whereKey($documentId)
            ->where('user_id', $candidate->id)
            ->where(function ($query) use ($request) {
                $query->where('application_id', $request->application_id)
                    ->orWhere('adhesion_registration_id', $this->requiredApplication($request)->adhesion_registration_id);
            })
            ->exists();

        if (! $belongs) {
            throw ValidationException::withMessages(['document_submission_id' => 'O documento selecionado não pertence a esta candidatura.']);
        }
    }

    private function requiredItem(CorrectionRequest $request, int $itemId): CorrectionRequestItem
    {
        return $request->items()->whereKey($itemId)->firstOrFail();
    }

    private function requiredCorrectionRequestItem(CorrectionResponse $response): CorrectionRequestItem
    {
        $item = $response->correctionRequestItem()->first();

        if (! $item instanceof CorrectionRequestItem) {
            throw ValidationException::withMessages(['correction_request_item' => 'Resposta sem item de aperfeiçoamento associado.']);
        }

        return $item;
    }

    private function requiredCorrectionRequest(CorrectionResponse $response): CorrectionRequest
    {
        $request = $response->correctionRequest;

        if (! $request instanceof CorrectionRequest) {
            throw ValidationException::withMessages(['correction_request' => 'Resposta sem pedido de aperfeiçoamento associado.']);
        }

        return $request;
    }

    private function requiredAdministrativeProcess(CorrectionRequest $request): AdministrativeProcess
    {
        $process = $request->administrativeProcess;

        if (! $process instanceof AdministrativeProcess) {
            throw ValidationException::withMessages(['process' => 'Pedido sem processo administrativo associado.']);
        }

        return $process;
    }

    private function processStatus(AdministrativeProcess $process): ?AdministrativeProcessStatus
    {
        $status = $process->getAttribute('status');

        if ($status instanceof AdministrativeProcessStatus) {
            return $status;
        }

        return is_string($status) ? AdministrativeProcessStatus::tryFrom($status) : null;
    }

    private function requiredApplication(CorrectionRequest $request): Application
    {
        $application = $request->application;

        if (! $application instanceof Application) {
            throw ValidationException::withMessages(['application' => 'Pedido sem candidatura associada.']);
        }

        return $application;
    }
}
