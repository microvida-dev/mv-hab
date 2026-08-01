<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionRevalidationAggregateResult;
use App\Events\CorrectionRevalidationProjected;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewBatchItem;
use App\Models\ApplicationReviewPublication;
use App\Models\ApplicationReviewPublicationResult;
use App\Models\CorrectionRequest;
use App\Models\CorrectionSubmissionReceipt;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Support\CanonicalJsonHasher;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PublishedCorrectionRevalidationProjector
{
    private const PUBLICATION_EVENT_CODE = 'application_review_result_published';

    public function __construct(
        private readonly AdministrativeWorkflowTransitionService $transitions,
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly CanonicalJsonHasher $hasher,
        private readonly AuditLogger $audit,
    ) {}

    public function project(
        ApplicationReviewPublicationResult $result,
        User $actor,
    ): CorrectionRequest {
        if (
            $actor->hasRole(['candidate', 'auditor'])
            || ! $actor->hasPermission('administrative_processes.publish')
        ) {
            abort(403);
        }

        return DB::transaction(function () use ($result, $actor): CorrectionRequest {
            $reference = ApplicationReviewPublicationResult::query()
                ->select([
                    'id',
                    'application_review_publication_id',
                    'application_review_batch_item_id',
                ])
                ->findOrFail($result->id);
            $itemReference = ApplicationReviewBatchItem::query()
                ->select(['id', 'application_review_batch_id'])
                ->findOrFail($reference->application_review_batch_item_id);
            $batch = ApplicationReviewBatch::query()
                ->whereKey($itemReference->application_review_batch_id)
                ->lockForUpdate()
                ->firstOrFail();
            $publication = ApplicationReviewPublication::query()
                ->whereKey($reference->application_review_publication_id)
                ->lockForUpdate()
                ->firstOrFail();
            $item = ApplicationReviewBatchItem::query()
                ->whereKey($itemReference->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedResult = ApplicationReviewPublicationResult::query()
                ->whereKey($reference->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($batch->correction_request_id === null) {
                throw $this->invalidProjection(
                    'O lote de revalidação não identifica o pedido de aperfeiçoamento.',
                );
            }

            $request = $this->municipalScope
                ->correctionRequests(CorrectionRequest::query(), $actor)
                ->whereKey($batch->correction_request_id)
                ->lockForUpdate()
                ->first();

            if (! $request instanceof CorrectionRequest) {
                abort(403);
            }

            $process = AdministrativeProcess::query()
                ->whereKey($request->administrative_process_id)
                ->lockForUpdate()
                ->firstOrFail();
            $application = Application::query()
                ->whereKey($request->application_id)
                ->lockForUpdate()
                ->firstOrFail();
            $originalResult = ApplicationReviewPublicationResult::query()
                ->whereKey($request->application_review_publication_result_id)
                ->firstOrFail();
            $receipt = CorrectionSubmissionReceipt::query()
                ->where('correction_request_id', $request->id)
                ->firstOrFail();
            $aggregate = $this->aggregateResult($lockedResult);

            $this->assertIntegrity(
                batch: $batch,
                publication: $publication,
                item: $item,
                result: $lockedResult,
                request: $request,
                process: $process,
                application: $application,
                originalResult: $originalResult,
                receipt: $receipt,
                aggregate: $aggregate,
            );

            if ($request->revalidation_publication_result_id !== null) {
                return $this->alreadyProjected(
                    $request,
                    $process,
                    $lockedResult,
                    $aggregate,
                );
            }

            if (
                $request->status !== CorrectionRequestStatus::Submitted
                || $request->revalidation_started_at === null
                || $process->status !== AdministrativeProcessStatus::CorrectionUnderReview
                || $request->revalidation_result !== null
                || $request->revalidation_projected_by !== null
                || $request->revalidation_projected_at !== null
                || $request->resolved_at !== null
                || $request->closed_at !== null
            ) {
                throw $this->invalidProjection(
                    'O pedido ou o processo não se encontra num estado projetável.',
                );
            }

            $projectedAt = now('UTC');
            $this->transitions->transition(
                $process,
                AdministrativeProcessStatus::EligibilityReview,
                $actor,
                'Resultado publicado da segunda análise documental.',
            );
            $request->forceFill([
                'status' => CorrectionRequestStatus::Resolved,
                'revalidation_result' => $aggregate,
                'revalidation_publication_result_id' => $lockedResult->id,
                'revalidation_projected_by' => $actor->id,
                'revalidation_projected_at' => $projectedAt,
                'resolved_at' => $projectedAt,
                'closed_at' => $projectedAt,
            ])->save();

            $context = $this->auditContext(
                request: $request,
                result: $lockedResult,
                actor: $actor,
                aggregate: $aggregate,
                projectedAt: $projectedAt->toIso8601String(),
            );
            $this->audit->record(
                event: AuditEvents::PUBLISH,
                auditable: $lockedResult,
                module: 'administrative_processes',
                action: 'correction_revalidation_published',
                description: 'Resultado final da segunda análise publicado.',
                newValues: [
                    'outcome' => $lockedResult->outcome->value,
                    'result_hash' => $lockedResult->result_hash,
                ],
                metadata: $context,
            );

            if ($aggregate === CorrectionRevalidationAggregateResult::Rejected) {
                $this->audit->record(
                    event: AuditEvents::REJECT,
                    auditable: $request,
                    module: 'administrative_processes',
                    action: 'correction_revalidation_rejected',
                    description: 'Segunda análise concluída com elemento não aceite.',
                    newValues: ['result' => $aggregate->value],
                    metadata: $context,
                );
            }

            $this->audit->record(
                event: AuditEvents::UPDATE,
                auditable: $request,
                module: 'administrative_processes',
                action: 'correction_request_resolved',
                description: 'Pedido de aperfeiçoamento resolvido após publicação.',
                newValues: [
                    'status' => CorrectionRequestStatus::Resolved->value,
                    'result' => $aggregate->value,
                ],
                metadata: $context,
            );
            $this->audit->record(
                event: AuditEvents::UPDATE,
                auditable: $request,
                module: 'administrative_processes',
                action: 'correction_revalidation_projected',
                description: 'Resultado publicado projetado no pedido e no processo.',
                newValues: [
                    'publication_result_id' => (int) $lockedResult->id,
                    'process_status' => AdministrativeProcessStatus::EligibilityReview->value,
                ],
                metadata: $context,
            );

            CorrectionRevalidationProjected::dispatch(
                municipalityId: (int) $lockedResult->municipality_id,
                contestId: (int) $lockedResult->contest_id,
                administrativeProcessId: (int) $process->id,
                applicationId: (int) $application->id,
                correctionRequestId: (int) $request->id,
                publicationResultId: (int) $lockedResult->id,
                outcome: $aggregate->value,
                projectedBy: (int) $actor->id,
                projectedAt: $projectedAt->toIso8601String(),
            );

            return $request->refresh()->load([
                'revalidationPublicationResult',
                'revalidationProjectedBy',
                'administrativeProcess',
            ]);
        }, 3);
    }

    private function aggregateResult(
        ApplicationReviewPublicationResult $result,
    ): CorrectionRevalidationAggregateResult {
        return match ($result->outcome) {
            ApplicationReviewBatchOutcome::CompletePendingDecision => CorrectionRevalidationAggregateResult::Accepted,
            ApplicationReviewBatchOutcome::CorrectionRejected => CorrectionRevalidationAggregateResult::Rejected,
            default => throw $this->invalidProjection(
                'O resultado publicado não corresponde a um fecho de segunda análise.',
            ),
        };
    }

    private function assertIntegrity(
        ApplicationReviewBatch $batch,
        ApplicationReviewPublication $publication,
        ApplicationReviewBatchItem $item,
        ApplicationReviewPublicationResult $result,
        CorrectionRequest $request,
        AdministrativeProcess $process,
        Application $application,
        ApplicationReviewPublicationResult $originalResult,
        CorrectionSubmissionReceipt $receipt,
        CorrectionRevalidationAggregateResult $aggregate,
    ): void {
        $snapshot = $item->snapshot_payload;
        $snapshotRequest = is_array($snapshot['correction_request'] ?? null)
            ? $snapshot['correction_request']
            : [];
        $snapshotOriginal = is_array($snapshot['original_publication_result'] ?? null)
            ? $snapshot['original_publication_result']
            : [];
        $snapshotReceipt = is_array($snapshot['submission_receipt'] ?? null)
            ? $snapshot['submission_receipt']
            : [];
        $snapshotAggregate = is_array($snapshot['aggregate_result'] ?? null)
            ? $snapshot['aggregate_result']
            : [];
        $candidatePayload = $result->result_payload;
        $batchHash = $this->hasher->hash([
            'schema_version' => 1,
            'contest_id' => (int) $batch->contest_id,
            'cycle' => $batch->cycle->value,
            'items' => [[
                'application_id' => (int) $item->application_id,
                'snapshot_hash' => $item->snapshot_hash,
                'payload' => $snapshot,
            ]],
        ]);
        $notificationHash = $this->hasher->hash([
            'event_code' => self::PUBLICATION_EVENT_CODE,
            'result_hash' => $result->result_hash,
        ]);

        $valid = $batch->cycle === ApplicationReviewBatchCycle::Revalidation
            && $batch->status === ApplicationReviewBatchStatus::Sealed
            && $batch->item_count === 1
            && $batch->items()->count() === 1
            && (int) $batch->correction_request_id === (int) $request->id
            && $publication->cycle === ApplicationReviewBatchCycle::Revalidation
            && (int) $publication->application_review_batch_id === (int) $batch->id
            && (int) $publication->municipality_id === (int) $batch->municipality_id
            && (int) $publication->contest_id === (int) $batch->contest_id
            && $publication->item_count === 1
            && hash_equals($publication->source_snapshot_hash, $batch->snapshot_hash)
            && (int) $item->application_review_batch_id === (int) $batch->id
            && (int) $result->application_review_publication_id === (int) $publication->id
            && (int) $result->application_review_batch_item_id === (int) $item->id
            && (int) $result->municipality_id === (int) $batch->municipality_id
            && (int) $result->contest_id === (int) $batch->contest_id
            && (int) $result->administrative_process_id === (int) $process->id
            && (int) $result->application_id === (int) $application->id
            && (int) $result->user_id === (int) $request->user_id
            && (int) $process->application_id === (int) $application->id
            && (int) $process->contest_id === (int) $batch->contest_id
            && (int) $process->user_id === (int) $request->user_id
            && (int) $application->contest_id === (int) $batch->contest_id
            && (int) $application->user_id === (int) $request->user_id
            && $item->outcome === $result->outcome
            && (string) $item->technical_result === (string) $result->technical_result
            && (string) ($snapshot['schema_version'] ?? '') === '1'
            && ($snapshot['cycle'] ?? null) === ApplicationReviewBatchCycle::Revalidation->value
            && ($snapshot['outcome'] ?? null) === $result->outcome->value
            && ($snapshot['technical_result'] ?? null) === $result->technical_result
            && ($snapshotAggregate['value'] ?? null) === $aggregate->value
            && (int) ($snapshotRequest['id'] ?? 0) === (int) $request->id
            && ($snapshotRequest['number'] ?? null) === $request->request_number
            && ($snapshotRequest['source_snapshot_hash'] ?? null) === $request->source_snapshot_hash
            && (int) ($snapshotOriginal['id'] ?? 0) === (int) $originalResult->id
            && ($snapshotOriginal['public_id'] ?? null) === $originalResult->public_id
            && ($snapshotOriginal['source_snapshot_hash'] ?? null) === $originalResult->source_snapshot_hash
            && ($snapshotOriginal['result_hash'] ?? null) === $originalResult->result_hash
            && (int) ($snapshotReceipt['id'] ?? 0) === (int) $receipt->id
            && ($snapshotReceipt['number'] ?? null) === $receipt->receipt_number
            && ($snapshotReceipt['snapshot_hash'] ?? null) === $receipt->snapshot_hash
            && hash_equals($receipt->snapshot_hash, $this->hasher->hash($receipt->snapshot_payload))
            && hash_equals($item->snapshot_hash, $this->hasher->hash($snapshot))
            && hash_equals($batch->snapshot_hash, $batchHash)
            && hash_equals($item->source_fingerprint, (string) ($snapshot['source_fingerprint'] ?? ''))
            && hash_equals($batch->source_fingerprint, $item->source_fingerprint)
            && hash_equals($result->source_snapshot_hash, $item->snapshot_hash)
            && hash_equals((string) ($candidatePayload['source_snapshot_hash'] ?? ''), $result->source_snapshot_hash)
            && ($candidatePayload['cycle'] ?? null) === ApplicationReviewBatchCycle::Revalidation->value
            && ($candidatePayload['outcome'] ?? null) === $result->outcome->value
            && ($candidatePayload['next_action'] ?? null) === 'await_formal_decision'
            && hash_equals($result->result_hash, $this->hasher->hash($candidatePayload))
            && hash_equals($result->notification_hash, $notificationHash)
            && (int) $request->application_review_publication_result_id === (int) $originalResult->id
            && hash_equals((string) $request->source_snapshot_hash, $originalResult->source_snapshot_hash);

        if (! $valid) {
            throw $this->invalidProjection(
                'A integridade ou o contexto da publicação de revalidação não pôde ser confirmado.',
            );
        }
    }

    private function alreadyProjected(
        CorrectionRequest $request,
        AdministrativeProcess $process,
        ApplicationReviewPublicationResult $result,
        CorrectionRevalidationAggregateResult $aggregate,
    ): CorrectionRequest {
        $coherent = (int) $request->revalidation_publication_result_id === (int) $result->id
            && $request->revalidation_result === $aggregate
            && $request->status === CorrectionRequestStatus::Resolved
            && $request->revalidation_projected_by !== null
            && $request->revalidation_projected_at !== null
            && $request->resolved_at !== null
            && $request->closed_at !== null
            && $process->status === AdministrativeProcessStatus::EligibilityReview;

        if (! $coherent) {
            throw $this->invalidProjection(
                'A projeção existente encontra-se parcial ou incoerente.',
            );
        }

        return $request->load([
            'revalidationPublicationResult',
            'revalidationProjectedBy',
            'administrativeProcess',
        ]);
    }

    /** @return array<string, int|string> */
    private function auditContext(
        CorrectionRequest $request,
        ApplicationReviewPublicationResult $result,
        User $actor,
        CorrectionRevalidationAggregateResult $aggregate,
        string $projectedAt,
    ): array {
        return [
            'actor_id' => (int) $actor->id,
            'municipality_id' => (int) $result->municipality_id,
            'contest_id' => (int) $result->contest_id,
            'administrative_process_id' => (int) $request->administrative_process_id,
            'application_id' => (int) $request->application_id,
            'correction_request_id' => (int) $request->id,
            'publication_result_id' => (int) $result->id,
            'result' => $aggregate->value,
            'result_hash' => $result->result_hash,
            'projected_at' => $projectedAt,
        ];
    }

    private function invalidProjection(string $message): ValidationException
    {
        return ValidationException::withMessages([
            'revalidation' => $message,
        ]);
    }
}
