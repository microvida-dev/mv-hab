<?php

namespace App\Services\Administrative;

use App\Data\Administrative\ReviewBatchSelectionItem;
use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\ApplicationReviewResult;
use App\Enums\ApplicationReviewStatus;
use App\Enums\ApplicationReviewType;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReview;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewBatchItem;
use App\Models\Contest;
use App\Models\DocumentReview;
use App\Models\DocumentSubmission;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Support\CanonicalJsonHasher;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use JsonException;

class ApplicationReviewBatchService
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly ApplicationReviewReadinessService $readinessService,
        private readonly ReviewBatchSnapshotBuilder $snapshotBuilder,
        private readonly CanonicalJsonHasher $hasher,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return Collection<int, array{
     *     contest: Contest,
     *     process_count: int,
     *     batch_count: int,
     *     next_cycle: ApplicationReviewBatchCycle|null
     * }>
     */
    public function contestOverview(User $actor): Collection
    {
        $contests = $this->municipalScope
            ->contests(Contest::query(), $actor)
            ->with('program')
            ->withCount('administrativeProcesses')
            ->orderByDesc('id')
            ->get();

        $batches = ApplicationReviewBatch::query()
            ->whereIn('contest_id', $contests->pluck('id'))
            ->get(['id', 'contest_id', 'cycle']);

        return $contests->map(function (Contest $contest) use (
            $batches,
        ): array {
            $contestBatches = $batches
                ->filter(
                    fn (ApplicationReviewBatch $batch): bool => $batch
                        ->contest_id === $contest->id,
                )
                ->values();
            $existingCycles = $contestBatches
                ->map(fn (ApplicationReviewBatch $batch): string => $batch
                    ->cycle
                    ->value)
                ->all();

            /** @var array{
             *     contest: Contest,
             *     process_count: int,
             *     batch_count: int,
             *     next_cycle: ApplicationReviewBatchCycle|null
             * } $overview
             */
            $overview = [
                'contest' => $contest,
                'process_count' => (int) $contest
                    ->getAttribute('administrative_processes_count'),
                'batch_count' => $contestBatches->count(),
                'next_cycle' => $this->nextCycle($existingCycles),
            ];

            return $overview;
        });
    }

    /**
     * @return Collection<int, ApplicationReviewBatch>
     */
    public function batchesForContest(
        Contest $contest,
        User $actor,
    ): Collection {
        $this->assertContestScope($contest, $actor);

        return ApplicationReviewBatch::query()
            ->where('contest_id', $contest->id)
            ->with(['sealedBy'])
            ->orderBy('sequence_number')
            ->get();
    }

    /**
     * @return array{
     *     cycle: ApplicationReviewBatchCycle,
     *     process_ids: list<int>,
     *     items: list<ReviewBatchSelectionItem>,
     *     blockers: list<string>
     * }
     */
    public function inspectContest(
        Contest $contest,
        User $actor,
    ): array {
        $this->assertContestScope($contest, $actor);

        $existingCycles = ApplicationReviewBatch::query()
            ->where('contest_id', $contest->id)
            ->pluck('cycle')
            ->map(fn (mixed $cycle): string => (string) $cycle)
            ->all();
        $cycle = $this->nextCycle($existingCycles)
            ?? ApplicationReviewBatchCycle::Revalidation;
        $processIds = $this->allProcessIds($contest, $actor);
        $selection = $this->buildSelection(
            $contest,
            $actor,
            $cycle,
            $processIds,
            false,
        );

        return [
            'cycle' => $cycle,
            'process_ids' => $processIds,
            'items' => $selection['items'],
            'blockers' => $selection['blockers'],
        ];
    }

    /**
     * @param  array{
     *     cycle: ApplicationReviewBatchCycle,
     *     process_ids: list<int>,
     *     reason: string,
     *     preview_token: string|null
     * }  $payload
     * @return array{
     *     cycle: ApplicationReviewBatchCycle,
     *     cycle_label: string,
     *     process_ids: list<int>,
     *     reason: string,
     *     items: list<ReviewBatchSelectionItem>,
     *     blockers: list<string>,
     *     source_fingerprint: string,
     *     snapshot_hash: string,
     *     token: string
     * }
     */
    public function preview(
        Contest $contest,
        User $actor,
        array $payload,
    ): array {
        $selection = $this->buildSelection(
            $contest,
            $actor,
            $payload['cycle'],
            $payload['process_ids'],
            false,
        );

        return $this->previewPayload(
            $contest,
            $actor,
            $payload,
            $selection['items'],
            $selection['blockers'],
        );
    }

    /**
     * @param  array{
     *     cycle: ApplicationReviewBatchCycle,
     *     process_ids: list<int>,
     *     reason: string,
     *     preview_token: string|null
     * }  $payload
     */
    public function seal(
        Contest $contest,
        User $actor,
        array $payload,
    ): ApplicationReviewBatch {
        $token = $payload['preview_token'];

        if (! is_string($token)) {
            throw ValidationException::withMessages([
                'preview_token' => 'O lote deve ser previamente confirmado.',
            ]);
        }

        $sealKey = hash('sha256', $token);
        $existing = ApplicationReviewBatch::query()
            ->where('seal_key', $sealKey)
            ->where('contest_id', $contest->id)
            ->first();

        if ($existing instanceof ApplicationReviewBatch) {
            return $existing->load(['items', 'sealedBy', 'contest']);
        }

        return DB::transaction(function () use (
            $contest,
            $actor,
            $payload,
            $token,
            $sealKey,
        ): ApplicationReviewBatch {
            $lockedContest = Contest::query()
                ->whereKey($contest->id)
                ->with('program')
                ->lockForUpdate()
                ->firstOrFail();
            $this->assertContestScope($lockedContest, $actor);

            $existingByKey = ApplicationReviewBatch::query()
                ->where('seal_key', $sealKey)
                ->lockForUpdate()
                ->first();

            if ($existingByKey instanceof ApplicationReviewBatch) {
                return $existingByKey->load([
                    'items',
                    'sealedBy',
                    'contest',
                ]);
            }

            $selection = $this->buildSelection(
                $lockedContest,
                $actor,
                $payload['cycle'],
                $payload['process_ids'],
                true,
            );
            $preview = $this->previewPayload(
                $lockedContest,
                $actor,
                $payload,
                $selection['items'],
                $selection['blockers'],
            );

            if (! hash_equals($preview['token'], $token)) {
                throw ValidationException::withMessages([
                    'preview_token' => 'Os dados do concurso foram alterados. Gere uma nova pré-visualização.',
                ]);
            }

            if ($preview['blockers'] !== []) {
                throw ValidationException::withMessages([
                    'process_ids' => $preview['blockers'],
                ]);
            }

            $existingSource = ApplicationReviewBatch::query()
                ->where('source_fingerprint', $preview['source_fingerprint'])
                ->lockForUpdate()
                ->first();

            if ($existingSource instanceof ApplicationReviewBatch) {
                return $existingSource->load([
                    'items',
                    'sealedBy',
                    'contest',
                ]);
            }

            $program = $lockedContest->program;
            $municipalityId = $program instanceof Program
                ? $program->municipality_id
                : null;

            if (! is_int($municipalityId)) {
                throw ValidationException::withMessages([
                    'contest' => 'O concurso não possui Município autoritativo.',
                ]);
            }

            $sequence = (int) ApplicationReviewBatch::query()
                ->where('contest_id', $lockedContest->id)
                ->lockForUpdate()
                ->max('sequence_number') + 1;

            $batch = new ApplicationReviewBatch;
            $batch->forceFill([
                'municipality_id' => $municipalityId,
                'contest_id' => $lockedContest->id,
                'cycle' => $payload['cycle'],
                'sequence_number' => $sequence,
                'status' => ApplicationReviewBatchStatus::Sealed,
                'reason' => $payload['reason'],
                'item_count' => count($preview['items']),
                'seal_key' => $sealKey,
                'source_fingerprint' => $preview['source_fingerprint'],
                'snapshot_hash' => $preview['snapshot_hash'],
                'sealed_by' => $actor->id,
                'sealed_at' => now(),
            ]);
            $batch->save();

            foreach ($preview['items'] as $item) {
                $technicalResult = $this->technicalResult($item->outcome);

                $batchItem = new ApplicationReviewBatchItem;
                $batchItem->forceFill([
                    'application_review_batch_id' => $batch->id,
                    'administrative_process_id' => $item->process->id,
                    'application_id' => $item->application->id,
                    'application_review_id' => $item->review?->id,
                    'process_number' => $item->process->process_number,
                    'application_number' => $item->application
                        ->application_number,
                    'application_public_id' => $item->application->public_id,
                    'outcome' => $item->outcome,
                    'technical_result' => $technicalResult->value,
                    'review_lock_version' => $item->review?->lock_version,
                    'readiness_snapshot' => $item->readiness,
                    'document_snapshot' => $item->snapshotPayload['documents'],
                    'snapshot_payload' => $item->snapshotPayload,
                    'source_fingerprint' => $item->sourceFingerprint,
                    'snapshot_hash' => $item->snapshotHash,
                ]);
                $batchItem->save();

                $this->completeReview($item, $technicalResult, $actor, $batch);
            }

            $outcomes = collect($preview['items'])
                ->countBy(fn (ReviewBatchSelectionItem $item): string => $item
                    ->outcome
                    ->value)
                ->all();

            $this->auditLogger->record(
                event: AuditEvents::CREATE,
                auditable: $batch,
                module: 'administrative_processes',
                action: 'application_review_batch_sealed',
                description: 'Lote coletivo de revisão selado com snapshots imutáveis.',
                newValues: [
                    'status' => ApplicationReviewBatchStatus::Sealed->value,
                    'cycle' => $payload['cycle']->value,
                    'item_count' => count($preview['items']),
                    'snapshot_hash' => $preview['snapshot_hash'],
                ],
                metadata: [
                    'actor_id' => $actor->id,
                    'contest_id' => $lockedContest->id,
                    'sequence_number' => $sequence,
                    'outcomes' => $outcomes,
                ],
            );

            return $batch->load(['items', 'sealedBy', 'contest']);
        }, 3);
    }

    /**
     * @param  list<ReviewBatchSelectionItem>  $items
     * @param  list<string>  $blockers
     * @param  array{
     *     cycle: ApplicationReviewBatchCycle,
     *     process_ids: list<int>,
     *     reason: string,
     *     preview_token: string|null
     * }  $payload
     * @return array{
     *     cycle: ApplicationReviewBatchCycle,
     *     cycle_label: string,
     *     process_ids: list<int>,
     *     reason: string,
     *     items: list<ReviewBatchSelectionItem>,
     *     blockers: list<string>,
     *     source_fingerprint: string,
     *     snapshot_hash: string,
     *     token: string
     * }
     *
     * @throws JsonException
     */
    private function previewPayload(
        Contest $contest,
        User $actor,
        array $payload,
        array $items,
        array $blockers,
    ): array {
        $itemSources = array_map(
            fn (ReviewBatchSelectionItem $item): array => [
                'process_id' => $item->process->id,
                'application_id' => $item->application->id,
                'outcome' => $item->outcome->value,
                'source_fingerprint' => $item->sourceFingerprint,
            ],
            $items,
        );
        $itemSnapshots = array_map(
            fn (ReviewBatchSelectionItem $item): array => [
                'application_id' => $item->application->id,
                'snapshot_hash' => $item->snapshotHash,
                'payload' => $item->snapshotPayload,
            ],
            $items,
        );
        $sourceFingerprint = $this->hasher->hash([
            'schema_version' => 1,
            'contest_id' => $contest->id,
            'cycle' => $payload['cycle']->value,
            'items' => $itemSources,
        ]);
        $snapshotHash = $this->hasher->hash([
            'schema_version' => 1,
            'contest_id' => $contest->id,
            'cycle' => $payload['cycle']->value,
            'items' => $itemSnapshots,
        ]);
        $token = hash_hmac(
            'sha256',
            $this->hasher->encode([
                'actor_id' => $actor->id,
                'contest_id' => $contest->id,
                'cycle' => $payload['cycle']->value,
                'process_ids' => $payload['process_ids'],
                'reason' => $payload['reason'],
                'source_fingerprint' => $sourceFingerprint,
                'snapshot_hash' => $snapshotHash,
            ]),
            (string) config('app.key'),
        );

        return [
            'cycle' => $payload['cycle'],
            'cycle_label' => $payload['cycle']->label(),
            'process_ids' => $payload['process_ids'],
            'reason' => $payload['reason'],
            'items' => $items,
            'blockers' => $blockers,
            'source_fingerprint' => $sourceFingerprint,
            'snapshot_hash' => $snapshotHash,
            'token' => $token,
        ];
    }

    /**
     * @param  list<int>  $requestedIds
     * @return array{items:list<ReviewBatchSelectionItem>, blockers:list<string>}
     */
    private function buildSelection(
        Contest $contest,
        User $actor,
        ApplicationReviewBatchCycle $cycle,
        array $requestedIds,
        bool $lockForUpdate,
    ): array {
        $this->assertContestScope($contest, $actor);
        $allIds = $this->allProcessIds($contest, $actor, $lockForUpdate);
        $requestedIds = array_values(array_unique($requestedIds));
        sort($requestedIds, SORT_NUMERIC);

        if ($requestedIds !== $allIds) {
            throw ValidationException::withMessages([
                'process_ids' => 'O lote deve abranger todos os processos do concurso nesta fase.',
            ]);
        }

        if ($requestedIds === []) {
            throw ValidationException::withMessages([
                'process_ids' => 'O concurso não possui processos para fechar.',
            ]);
        }

        $processQuery = $this->municipalScope
            ->administrativeProcesses(
                AdministrativeProcess::query(),
                $actor,
            )
            ->where('contest_id', $contest->id)
            ->whereIn('id', $requestedIds)
            ->orderBy('id');

        if ($lockForUpdate) {
            $processQuery->lockForUpdate();
        }

        $processes = $processQuery->get();
        $applications = $this->applications(
            $processes,
            $contest,
            $lockForUpdate,
        );
        $reviews = $this->reviews($processes, $lockForUpdate);
        $documents = $this->documents(
            $applications,
            $actor,
            $lockForUpdate,
        );

        Gate::forUser($actor)->authorize(
            'viewAnyBackoffice',
            DocumentSubmission::class,
        );

        $items = [];
        $blockers = $this->cycleBlockers($contest, $cycle);

        foreach ($processes as $process) {
            $application = $applications->get($process->application_id);

            if (! $application instanceof Application) {
                $blockers[] = sprintf(
                    '%s: candidatura indisponível.',
                    $process->process_number,
                );

                continue;
            }

            $process->setRelation('application', $application);
            $reviewCandidate = $reviews->get($process->id);
            $review = $reviewCandidate instanceof ApplicationReview
                ? $reviewCandidate
                : null;
            $process->setRelation('latestDocumentalReview', $review);

            Gate::forUser($actor)->authorize(
                $process->isClosed() ? 'viewBackoffice' : 'update',
                $process,
            );

            $processDocuments = $documents->filter(
                fn (DocumentSubmission $document): bool => $document
                    ->application_id === $application->id
                    || $document->adhesion_registration_id
                        === $application->adhesion_registration_id,
            )->values();
            $readiness = $this->readinessService->forProcess($process);
            [$outcome, $processBlockers] = $this->outcome(
                $process,
                $review,
                $readiness,
            );

            foreach ($processBlockers as $blocker) {
                $blockers[] = sprintf(
                    '%s: %s',
                    $process->process_number,
                    $blocker,
                );
            }

            if (! $outcome instanceof ApplicationReviewBatchOutcome) {
                continue;
            }

            $snapshot = $this->snapshotBuilder->build(
                $process,
                $application,
                $review,
                $outcome,
                $readiness,
                $processDocuments,
            );
            $items[] = new ReviewBatchSelectionItem(
                process: $process,
                application: $application,
                review: $review,
                outcome: $outcome,
                readiness: $readiness,
                documents: $processDocuments,
                snapshotPayload: $snapshot['payload'],
                sourceFingerprint: $snapshot['source_fingerprint'],
                snapshotHash: $snapshot['snapshot_hash'],
            );
        }

        return [
            'items' => $items,
            'blockers' => array_values(array_unique($blockers)),
        ];
    }

    /**
     * @param  Collection<int, AdministrativeProcess>  $processes
     * @return Collection<int, Application>
     */
    private function applications(
        Collection $processes,
        Contest $contest,
        bool $lockForUpdate,
    ): Collection {
        $query = Application::query()
            ->where('contest_id', $contest->id)
            ->whereIn('id', $processes->pluck('application_id'))
            ->orderBy('id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get()->keyBy('id');
    }

    /**
     * @param  Collection<int, AdministrativeProcess>  $processes
     * @return Collection<int, ApplicationReview>
     */
    private function reviews(
        Collection $processes,
        bool $lockForUpdate,
    ): Collection {
        $query = ApplicationReview::query()
            ->whereIn('administrative_process_id', $processes->pluck('id'))
            ->where('review_type', ApplicationReviewType::Documental->value)
            ->whereNotIn('status', [
                ApplicationReviewStatus::Completed->value,
                ApplicationReviewStatus::Cancelled->value,
            ])
            ->orderBy('administrative_process_id')
            ->orderByDesc('id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get()
            ->unique('administrative_process_id')
            ->keyBy('administrative_process_id');
    }

    /**
     * @param  Collection<int, Application>  $applications
     * @return Collection<int, DocumentSubmission>
     */
    private function documents(
        Collection $applications,
        User $actor,
        bool $lockForUpdate,
    ): Collection {
        $applicationIds = $applications->pluck('id')->all();
        $registrationIds = $applications
            ->pluck('adhesion_registration_id')
            ->filter()
            ->all();
        $query = $this->municipalScope
            ->documentSubmissions(DocumentSubmission::query(), $actor)
            ->where(function (Builder $documents) use (
                $applicationIds,
                $registrationIds,
            ): void {
                $documents->whereIn('application_id', $applicationIds);

                if ($registrationIds !== []) {
                    $documents->orWhereIn(
                        'adhesion_registration_id',
                        $registrationIds,
                    );
                }
            })
            ->orderBy('id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $documents = $query->get();
        $reviewQuery = DocumentReview::query()
            ->whereIn('document_submission_id', $documents->pluck('id'))
            ->orderBy('id');

        if ($lockForUpdate) {
            $reviewQuery->lockForUpdate();
        }

        $reviews = $reviewQuery->get()->groupBy('document_submission_id');

        foreach ($documents as $document) {
            $document->setRelation(
                'reviews',
                $reviews->get($document->id, new EloquentCollection),
            );
        }

        return $documents;
    }

    /**
     * @param  array{
     *     ready: bool,
     *     total_required: int,
     *     validated: int,
     *     submitted: int,
     *     under_review: int,
     *     missing: int,
     *     rejected: int,
     *     expired: int,
     *     blockers: list<string>
     * }  $readiness
     * @return array{ApplicationReviewBatchOutcome|null, list<string>}
     */
    private function outcome(
        AdministrativeProcess $process,
        ?ApplicationReview $review,
        array $readiness,
    ): array {
        if ($process->status === AdministrativeProcessStatus::Withdrawn) {
            return [ApplicationReviewBatchOutcome::Withdrawn, []];
        }

        if (in_array($process->status, [
            AdministrativeProcessStatus::Cancelled,
            AdministrativeProcessStatus::Archived,
        ], true)) {
            return [ApplicationReviewBatchOutcome::NotAssessed, []];
        }

        if (! $review instanceof ApplicationReview) {
            return [null, ['não existe análise documental ativa.']];
        }

        if ($readiness['submitted'] > 0 || $readiness['under_review'] > 0) {
            return [null, ['existem documentos ainda por decidir.']];
        }

        if ($readiness['missing'] > 0
            || $readiness['rejected'] > 0
            || $readiness['expired'] > 0) {
            return [ApplicationReviewBatchOutcome::CorrectionRequired, []];
        }

        if (! $readiness['ready']) {
            return [null, $readiness['blockers'] ?: [
                'a análise documental não está concluída.',
            ]];
        }

        if (! $review->isReadyForClosure()) {
            return [null, [
                'a análise conforme ainda não foi marcada como pronta para fecho.',
            ]];
        }

        return [
            ApplicationReviewBatchOutcome::CompletePendingDecision,
            [],
        ];
    }

    /** @return list<string> */
    private function cycleBlockers(
        Contest $contest,
        ApplicationReviewBatchCycle $cycle,
    ): array {
        $existing = ApplicationReviewBatch::query()
            ->where('contest_id', $contest->id)
            ->where('cycle', $cycle->value)
            ->exists();

        if ($existing) {
            return [sprintf(
                'O ciclo %s já possui um lote selado.',
                $cycle->label(),
            )];
        }

        if ($cycle === ApplicationReviewBatchCycle::Revalidation
            && ! ApplicationReviewBatch::query()
                ->where('contest_id', $contest->id)
                ->where(
                    'cycle',
                    ApplicationReviewBatchCycle::InitialReview->value,
                )
                ->exists()) {
            return [
                'A revalidação só pode ser selada depois da validação inicial.',
            ];
        }

        return [];
    }

    /**
     * @param  array<string>  $existingCycles
     */
    private function nextCycle(array $existingCycles): ?ApplicationReviewBatchCycle
    {
        if (! in_array(
            ApplicationReviewBatchCycle::InitialReview->value,
            $existingCycles,
            true,
        )) {
            return ApplicationReviewBatchCycle::InitialReview;
        }

        if (! in_array(
            ApplicationReviewBatchCycle::Revalidation->value,
            $existingCycles,
            true,
        )) {
            return ApplicationReviewBatchCycle::Revalidation;
        }

        return null;
    }

    /** @return list<int> */
    private function allProcessIds(
        Contest $contest,
        User $actor,
        bool $lockForUpdate = false,
    ): array {
        $query = $this->municipalScope
            ->administrativeProcesses(
                AdministrativeProcess::query(),
                $actor,
            )
            ->where('contest_id', $contest->id)
            ->orderBy('id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return array_values(
            $query->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all(),
        );
    }

    private function assertContestScope(
        Contest $contest,
        User $actor,
    ): void {
        abort_unless(
            $this->municipalScope->ownsContest($actor, $contest),
            403,
        );
    }

    private function technicalResult(
        ApplicationReviewBatchOutcome $outcome,
    ): ApplicationReviewResult {
        return match ($outcome) {
            ApplicationReviewBatchOutcome::CompletePendingDecision => ApplicationReviewResult::Passed,
            ApplicationReviewBatchOutcome::CorrectionRequired => ApplicationReviewResult::RequiresCorrection,
            ApplicationReviewBatchOutcome::Withdrawn,
            ApplicationReviewBatchOutcome::NotAssessed => ApplicationReviewResult::NotApplicable,
        };
    }

    private function completeReview(
        ReviewBatchSelectionItem $item,
        ApplicationReviewResult $result,
        User $actor,
        ApplicationReviewBatch $batch,
    ): void {
        $review = $item->review;

        if (! $review instanceof ApplicationReview) {
            return;
        }

        $oldStatus = $review->status;
        $review->forceFill([
            'status' => ApplicationReviewStatus::Completed,
            'result' => $result,
            'completed_at' => now(),
            'last_activity_at' => now(),
            'lock_version' => $review->lock_version + 1,
        ])->save();

        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $review,
            module: 'administrative_processes',
            action: 'application_review_sealed_in_batch',
            description: 'Análise documental concluída num lote coletivo selado.',
            oldValues: ['status' => $oldStatus->value],
            newValues: [
                'status' => ApplicationReviewStatus::Completed->value,
                'result' => $result->value,
            ],
            metadata: [
                'actor_id' => $actor->id,
                'batch_id' => $batch->id,
                'process_id' => $item->process->id,
                'outcome' => $item->outcome->value,
            ],
        );
    }
}
