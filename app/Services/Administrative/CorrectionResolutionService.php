<?php

namespace App\Services\Administrative;

use App\Contracts\Program53\Program53FaultInjector;
use App\Contracts\Program53\Program53MetricsRecorder;
use App\Data\Administrative\CorrectionDifferentialResultData;
use App\Data\Program53\Program53OperationalContext;
use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionRevalidationAggregateResult;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewBatchItem;
use App\Models\Contest;
use App\Models\CorrectionRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Support\CanonicalJsonHasher;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;

final class CorrectionResolutionService
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly CorrectionDifferentialResolver $differentialResolver,
        private readonly CorrectionRevalidationService $revalidation,
        private readonly CorrectionRevalidationSnapshotBuilder $snapshotBuilder,
        private readonly CanonicalJsonHasher $hasher,
        private readonly AuditLogger $audit,
        private readonly Program53FaultInjector $faults,
        private readonly Program53MetricsRecorder $metrics,
    ) {}

    /**
     * @return array{
     *     request:CorrectionRequest,
     *     differential:CorrectionDifferentialResultData,
     *     aggregate_result:CorrectionRevalidationAggregateResult,
     *     reason:string,
     *     item_snapshot:array<string, mixed>,
     *     item_snapshot_hash:string,
     *     batch_snapshot_hash:string,
     *     source_fingerprint:string,
     *     token:string
     * }
     *
     * @throws JsonException
     */
    public function preview(
        CorrectionRequest $request,
        User $actor,
        string $reason,
    ): array {
        $preview = $this->buildPreview($request, $actor, $reason);

        $this->audit->record(
            event: AuditEvents::ACCESS,
            auditable: $preview['request'],
            module: 'administrative_processes',
            action: 'correction_revalidation_previewed',
            description: 'Pré-visualização final da segunda análise gerada.',
            metadata: $this->auditContext($preview['request'], $actor) + [
                'aggregate_result' => $preview['aggregate_result']->value,
                'snapshot_hash' => $preview['item_snapshot_hash'],
                'source_fingerprint' => $preview['source_fingerprint'],
            ],
        );

        return $preview;
    }

    /**
     * @throws JsonException
     */
    public function seal(
        CorrectionRequest $request,
        User $actor,
        string $reason,
        string $previewToken,
    ): ApplicationReviewBatch {
        if (trim($previewToken) === '') {
            throw ValidationException::withMessages([
                'preview_token' => 'A segunda análise deve ser previamente confirmada.',
            ]);
        }

        $startedAt = hrtime(true);
        $context = new Program53OperationalContext(
            operationId: 'correction-seal-'.substr(
                hash('sha256', $previewToken),
                0,
                24,
            ),
            municipalityId: $actor->municipality_id !== null
                ? (int) $actor->municipality_id
                : null,
            correctionRequestId: (int) $request->id,
            stage: 'revalidation_seal',
        );

        $batch = DB::transaction(function () use (
            $request,
            $actor,
            $reason,
            $previewToken,
            $context,
        ): ApplicationReviewBatch {
            $lockedRequest = $this->municipalScope
                ->correctionRequests(CorrectionRequest::query(), $actor)
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();
            $existing = ApplicationReviewBatch::query()
                ->where('correction_request_id', $lockedRequest->id)
                ->lockForUpdate()
                ->first();
            $sealKey = hash('sha256', $previewToken);

            if ($existing instanceof ApplicationReviewBatch) {
                if (! hash_equals($existing->seal_key, $sealKey)) {
                    throw ValidationException::withMessages([
                        'revalidation' => 'A segunda análise já foi selada com outra confirmação.',
                    ]);
                }

                return $existing->load([
                    'items',
                    'sealedBy',
                    'contest',
                    'correctionRequest',
                ]);
            }

            $this->assertReadyRequest($lockedRequest);
            $process = AdministrativeProcess::query()
                ->whereKey($lockedRequest->administrative_process_id)
                ->lockForUpdate()
                ->firstOrFail();
            $application = Application::query()
                ->whereKey($lockedRequest->application_id)
                ->lockForUpdate()
                ->firstOrFail();
            $contest = Contest::query()
                ->whereKey($application->contest_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $process->status !== AdministrativeProcessStatus::CorrectionUnderReview
                || (int) $process->application_id !== (int) $application->id
                || (int) $process->contest_id !== (int) $contest->id
                || (int) $application->user_id !== (int) $lockedRequest->user_id
            ) {
                throw ValidationException::withMessages([
                    'revalidation' => 'O contexto processual da segunda análise é incoerente.',
                ]);
            }

            $preview = $this->buildPreview(
                $lockedRequest,
                $actor,
                $reason,
            );

            if (! hash_equals($preview['token'], $previewToken)) {
                throw ValidationException::withMessages([
                    'preview_token' => 'As decisões ou as fontes foram alteradas. Gere uma nova pré-visualização.',
                ]);
            }
            $this->faults->checkpoint(
                'after_snapshot_before_commit',
                $context->withStage('revalidation_snapshot'),
            );

            $result = $lockedRequest->publicationResult()
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) $result->contest_id !== (int) $contest->id
                || (int) $result->application_id !== (int) $application->id
            ) {
                throw ValidationException::withMessages([
                    'revalidation' => 'A origem publicada não corresponde ao processo em análise.',
                ]);
            }

            $sequence = (int) ApplicationReviewBatch::query()
                ->where('contest_id', $contest->id)
                ->lockForUpdate()
                ->max('sequence_number') + 1;
            $sealedAt = now('UTC');
            $batch = new ApplicationReviewBatch;
            $batch->forceFill([
                'municipality_id' => $result->municipality_id,
                'contest_id' => $contest->id,
                'correction_request_id' => $lockedRequest->id,
                'collective_scope_key' => null,
                'cycle' => ApplicationReviewBatchCycle::Revalidation,
                'sequence_number' => $sequence,
                'status' => ApplicationReviewBatchStatus::Sealed,
                'reason' => $preview['reason'],
                'item_count' => 1,
                'seal_key' => $sealKey,
                'source_fingerprint' => $preview['source_fingerprint'],
                'snapshot_hash' => $preview['batch_snapshot_hash'],
                'sealed_by' => $actor->id,
                'sealed_at' => $sealedAt,
            ])->save();
            $itemSnapshot = $preview['item_snapshot'];
            $batchItem = new ApplicationReviewBatchItem;
            $batchItem->forceFill([
                'application_review_batch_id' => $batch->id,
                'administrative_process_id' => $process->id,
                'application_id' => $application->id,
                'application_review_id' => null,
                'process_number' => $process->process_number,
                'application_number' => $application->application_number,
                'application_public_id' => $application->public_id,
                'outcome' => $itemSnapshot['outcome'],
                'technical_result' => $itemSnapshot['technical_result'],
                'review_lock_version' => null,
                'readiness_snapshot' => $itemSnapshot['readiness'],
                'document_snapshot' => $itemSnapshot['documents'],
                'snapshot_payload' => $itemSnapshot,
                'source_fingerprint' => $preview['source_fingerprint'],
                'snapshot_hash' => $preview['item_snapshot_hash'],
            ])->save();

            $this->audit->record(
                event: AuditEvents::CREATE,
                auditable: $batch,
                module: 'administrative_processes',
                action: 'correction_revalidation_sealed',
                description: 'Segunda análise selada com snapshot imutável.',
                newValues: [
                    'status' => ApplicationReviewBatchStatus::Sealed->value,
                    'aggregate_result' => $preview['aggregate_result']->value,
                    'snapshot_hash' => $preview['batch_snapshot_hash'],
                ],
                metadata: $this->auditContext($lockedRequest, $actor) + [
                    'batch_id' => (int) $batch->id,
                    'batch_item_id' => (int) $batchItem->id,
                    'sequence_number' => $sequence,
                    'source_fingerprint' => $preview['source_fingerprint'],
                    'sealed_at' => $sealedAt->toIso8601String(),
                ],
            );

            return $batch->load([
                'items',
                'sealedBy',
                'contest',
                'correctionRequest',
            ]);
        }, 3);

        $completedContext = new Program53OperationalContext(
            operationId: $context->operationId,
            municipalityId: $context->municipalityId,
            contestId: (int) $batch->contest_id,
            batchId: (int) $batch->id,
            correctionRequestId: $context->correctionRequestId,
            stage: 'revalidation_sealed',
        );
        $this->faults->checkpoint(
            'after_resolution_before_projection',
            $completedContext,
        );
        $this->metrics->record(
            'revalidation_duration',
            round((hrtime(true) - $startedAt) / 1_000_000, 3),
            $completedContext,
            ['result' => 'sealed'],
        );

        return $batch;
    }

    /**
     * @return array{
     *     request:CorrectionRequest,
     *     differential:CorrectionDifferentialResultData,
     *     aggregate_result:CorrectionRevalidationAggregateResult,
     *     reason:string,
     *     item_snapshot:array<string, mixed>,
     *     item_snapshot_hash:string,
     *     batch_snapshot_hash:string,
     *     source_fingerprint:string,
     *     token:string
     * }
     *
     * @throws JsonException
     */
    private function buildPreview(
        CorrectionRequest $request,
        User $actor,
        string $reason,
    ): array {
        if (! $this->municipalScope->ownsCorrectionRequest($actor, $request)) {
            abort(403);
        }

        $request->refresh();
        $this->assertReadyRequest($request);

        if ($request->revalidationBatch()->exists()) {
            throw ValidationException::withMessages([
                'revalidation' => 'A segunda análise já possui um lote selado.',
            ]);
        }

        $normalizedReason = trim($reason);

        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Indique a fundamentação para o fecho da segunda análise.',
            ]);
        }

        $differential = $this->differentialResolver->resolve($request);
        $aggregate = $this->revalidation->aggregateResult($differential);

        if (! $aggregate instanceof CorrectionRevalidationAggregateResult) {
            throw ValidationException::withMessages([
                'revalidation' => 'O resultado agregado da segunda análise não pôde ser determinado.',
            ]);
        }

        if ($aggregate === CorrectionRevalidationAggregateResult::RequiresManualDecision) {
            throw ValidationException::withMessages([
                'revalidation' => 'Existe uma decisão manual pendente que impede a selagem.',
            ]);
        }

        $snapshot = $this->snapshotBuilder->build(
            $differential,
            $aggregate,
        );
        $batchSnapshotHash = $this->hasher->hash([
            'schema_version' => 1,
            'contest_id' => (int) $differential->application->contest_id,
            'cycle' => ApplicationReviewBatchCycle::Revalidation->value,
            'items' => [[
                'application_id' => (int) $differential->application->id,
                'snapshot_hash' => $snapshot['snapshot_hash'],
                'payload' => $snapshot['payload'],
            ]],
        ]);
        $token = hash_hmac(
            'sha256',
            $this->hasher->encode([
                'actor_id' => (int) $actor->id,
                'correction_request_id' => (int) $request->id,
                'reason' => $normalizedReason,
                'source_fingerprint' => $differential->sourceFingerprint,
                'item_snapshot_hash' => $snapshot['snapshot_hash'],
                'batch_snapshot_hash' => $batchSnapshotHash,
            ]),
            (string) config('app.key'),
        );

        return [
            'request' => $differential->request,
            'differential' => $differential,
            'aggregate_result' => $aggregate,
            'reason' => $normalizedReason,
            'item_snapshot' => $snapshot['payload'],
            'item_snapshot_hash' => $snapshot['snapshot_hash'],
            'batch_snapshot_hash' => $batchSnapshotHash,
            'source_fingerprint' => $differential->sourceFingerprint,
            'token' => $token,
        ];
    }

    private function assertReadyRequest(CorrectionRequest $request): void
    {
        if (
            $request->isLegacy()
            || $request->status !== CorrectionRequestStatus::Submitted
            || $request->revalidation_started_at === null
            || $request->submissionReceipt()->doesntExist()
        ) {
            throw ValidationException::withMessages([
                'revalidation' => 'O pedido não se encontra pronto para fechar a segunda análise.',
            ]);
        }
    }

    /** @return array<string, int|null> */
    private function auditContext(
        CorrectionRequest $request,
        User $actor,
    ): array {
        $request->loadMissing('publicationResult');

        return [
            'actor_id' => (int) $actor->id,
            'municipality_id' => $request->publicationResult?->municipality_id,
            'contest_id' => $request->publicationResult?->contest_id,
            'administrative_process_id' => (int) $request->administrative_process_id,
            'application_id' => (int) $request->application_id,
            'correction_request_id' => (int) $request->id,
        ];
    }
}
