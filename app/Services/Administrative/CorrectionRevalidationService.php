<?php

namespace App\Services\Administrative;

use App\Data\Administrative\CorrectionDifferentialItemData;
use App\Data\Administrative\CorrectionDifferentialResultData;
use App\Enums\AdministrativeProcessStatus;
use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionResponseStatus;
use App\Enums\CorrectionRevalidationAggregateResult;
use App\Models\AdministrativeProcess;
use App\Models\ApplicationReviewBatch;
use App\Models\Contest;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\DocumentSubmission;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Support\CanonicalJsonHasher;
use App\Support\AuditEvents;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use JsonException;

final class CorrectionRevalidationService
{
    /** @var list<string> */
    private const QUEUE_STATUSES = [
        CorrectionRequestStatus::Submitted->value,
        CorrectionRequestStatus::Resolved->value,
    ];

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly CorrectionDifferentialResolver $differentialResolver,
        private readonly AdministrativeWorkflowTransitionService $transitions,
        private readonly CanonicalJsonHasher $hasher,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{
     *     contest_id:int|null,
     *     submitted_from:string,
     *     submitted_to:string,
     *     sla:string,
     *     technician_id:int|null,
     *     state:string,
     *     result:string,
     *     process_number:string,
     *     application_number:string
     * }  $filters
     * @return array{
     *     requests:LengthAwarePaginator<int, CorrectionRequest>,
     *     summary:array<string, int>,
     *     contests:Collection<int, Contest>,
     *     technicians:Collection<int, User>
     * }
     */
    public function queue(User $actor, array $filters): array
    {
        $query = $this->applyQueueFilters(
            $this->queueQuery($actor),
            $filters,
        );

        $requests = $query
            ->with([
                'application:id,application_number,contest_id',
                'administrativeProcess:id,process_number,assigned_to,status',
                'revalidationStartedBy:id,name',
                'revalidationBatch:id,public_id,correction_request_id,status,sealed_at',
            ])
            ->withCount([
                'responses',
                'responses as reviewed_responses_count' => static fn (Builder $responses): Builder => $responses
                    ->whereNotNull('review_result'),
            ])
            ->orderByRaw('CASE WHEN revalidation_started_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $contestIds = $this->queueQuery($actor)
            ->whereNotNull('application_review_publication_result_id')
            ->join(
                'application_review_publication_results',
                'application_review_publication_results.id',
                '=',
                'correction_requests.application_review_publication_result_id',
            )
            ->distinct()
            ->pluck('application_review_publication_results.contest_id');

        $technicianIds = $this->queueQuery($actor)
            ->whereNotNull('revalidation_started_by')
            ->distinct()
            ->pluck('revalidation_started_by');

        return [
            'requests' => $requests,
            'summary' => $this->queueSummary($actor),
            'contests' => Contest::query()
                ->whereIn('id', $contestIds)
                ->orderByDesc('id')
                ->get(['id', 'code', 'title']),
            'technicians' => User::query()
                ->whereIn('id', $technicianIds)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    /**
     * @return array{
     *     request:CorrectionRequest,
     *     differential:CorrectionDifferentialResultData|null,
     *     progress:array<string, int|bool>,
     *     aggregate_result:CorrectionRevalidationAggregateResult|null,
     *     decision_tokens:array<int, string>,
     *     documents:Collection<int, DocumentSubmission>,
     *     batch:ApplicationReviewBatch|null
     * }
     */
    public function workspace(
        CorrectionRequest $request,
        User $actor,
    ): array {
        $this->assertScope($request, $actor);
        $request->load([
            'publicationResult.publication',
            'submissionReceipt',
            'administrativeProcess.assignedTo',
            'application',
            'items.documentType',
            'items.requiredDocument',
            'responses.correctionRequestItem',
            'responses.reviewedBy',
            'revalidationStartedBy',
            'revalidationBatch.publication',
            'revalidationBatch.items',
            'revalidationPublicationResult',
        ]);

        $batch = $request->revalidationBatch;
        $differential = null;

        if ($request->status === CorrectionRequestStatus::Submitted) {
            $differential = $this->differentialResolver->resolve($request);
        }

        $reviewable = $differential?->reviewableItems() ?? [];
        $responses = $differential?->request->responses->keyBy('id')
            ?? collect();
        $reviewed = 0;
        $manual = 0;
        $decisionTokens = [];

        foreach ($reviewable as $item) {
            $response = $responses->get($item->correctionResponseId);

            if (! $response instanceof CorrectionResponse) {
                continue;
            }

            if ($this->hasCurrentDecision($response, $item)) {
                $reviewed++;

                if (
                    in_array($response->review_result, [
                        CorrectionResponseReviewResult::RequiresManualDecision,
                        CorrectionResponseReviewResult::RequiresMoreInformation,
                    ], true)
                ) {
                    $manual++;
                }
            }

            if ($response->review_result instanceof CorrectionResponseReviewResult) {
                $decisionTokens[(int) $response->id] = $this->decisionToken(
                    $response,
                );
            }
        }

        $aggregate = $differential instanceof CorrectionDifferentialResultData
            ? $this->aggregateResult($differential, false)
            : $request->revalidation_result;
        $differentialItems = $differential instanceof CorrectionDifferentialResultData
            ? $differential->items
            : [];
        $documentIds = collect($differentialItems)
            ->flatMap(static fn (CorrectionDifferentialItemData $item): array => [
                $item->sourceDocumentSubmissionId,
                $item->submittedDocumentSubmissionId,
            ])
            ->filter(static fn (mixed $id): bool => is_int($id))
            ->unique()
            ->values();
        $documents = $documentIds->isEmpty()
            ? collect()
            : $this->municipalScope
                ->documentSubmissions(DocumentSubmission::query(), $actor)
                ->whereIn('id', $documentIds)
                ->get()
                ->filter(
                    static fn (DocumentSubmission $document): bool => Gate::forUser($actor)
                        ->allows('viewBackoffice', $document),
                )
                ->keyBy('id');
        $total = count($reviewable);
        $pending = max(0, $total - $reviewed);

        return [
            'request' => $differential instanceof CorrectionDifferentialResultData
                ? $differential->request
                : $request,
            'differential' => $differential,
            'progress' => [
                'total' => $total,
                'reviewed' => $reviewed,
                'pending' => $pending,
                'manual' => $manual,
                'percentage' => $total === 0
                    ? 100
                    : (int) round(($reviewed / $total) * 100),
                'stale' => $differential instanceof CorrectionDifferentialResultData
                    && $differential->isStale(),
                'ready_to_seal' => $differential instanceof CorrectionDifferentialResultData
                    && $request->revalidation_started_at !== null
                    && $pending === 0
                    && $manual === 0
                    && ! $differential->isStale()
                    && ! $batch instanceof ApplicationReviewBatch,
            ],
            'aggregate_result' => $aggregate,
            'decision_tokens' => $decisionTokens,
            'documents' => $documents,
            'batch' => $batch instanceof ApplicationReviewBatch
                ? $batch
                : null,
        ];
    }

    public function start(
        CorrectionRequest $request,
        User $actor,
    ): CorrectionRequest {
        return DB::transaction(function () use ($request, $actor): CorrectionRequest {
            $locked = $this->lockedScopedRequest($request, $actor);
            $this->assertSubmittedCanonical($locked);
            $process = AdministrativeProcess::query()
                ->whereKey($locked->administrative_process_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->revalidation_started_at !== null) {
                if (
                    $process->status !== AdministrativeProcessStatus::CorrectionUnderReview
                    && ! $locked->revalidationBatch()->exists()
                ) {
                    throw ValidationException::withMessages([
                        'revalidation' => 'A segunda análise possui um estado processual incoerente.',
                    ]);
                }

                return $locked->refresh();
            }

            if ($process->status !== AdministrativeProcessStatus::CorrectionSubmitted) {
                throw ValidationException::withMessages([
                    'revalidation' => 'O processo não se encontra na fase de aperfeiçoamento submetido.',
                ]);
            }

            $differential = $this->differentialResolver->resolve($locked);

            if ($differential->isStale()) {
                throw ValidationException::withMessages([
                    'revalidation' => $differential->blockers,
                ]);
            }

            $responses = CorrectionResponse::query()
                ->where('correction_request_id', $locked->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($differential->reviewableItems() as $item) {
                $response = $responses->get($item->correctionResponseId);

                if (! $response instanceof CorrectionResponse) {
                    throw ValidationException::withMessages([
                        'revalidation' => 'Uma resposta submetida deixou de estar disponível.',
                    ]);
                }

                if ($response->review_result !== null) {
                    throw ValidationException::withMessages([
                        'revalidation' => 'Foi encontrada uma decisão anterior sem abertura formal da segunda análise.',
                    ]);
                }

                $response->forceFill([
                    'status' => CorrectionResponseStatus::UnderReview,
                    'differential_classification' => $item->classification,
                ])->save();
            }

            $this->transitions->transition(
                $process,
                AdministrativeProcessStatus::CorrectionUnderReview,
                $actor,
                'Segunda análise diferencial do aperfeiçoamento iniciada.',
            );

            $startedAt = now('UTC');
            $locked->forceFill([
                'revalidation_started_by' => $actor->id,
                'revalidation_started_at' => $startedAt,
            ])->save();

            $this->audit->record(
                event: AuditEvents::CREATE,
                auditable: $locked,
                module: 'administrative_processes',
                action: 'correction_revalidation_started',
                description: 'Segunda análise diferencial iniciada.',
                newValues: [
                    'revalidation_started_at' => $startedAt->toIso8601String(),
                ],
                metadata: $this->auditContext($locked, $actor) + [
                    'source_fingerprint' => $differential->sourceFingerprint,
                    'reviewable_items' => count($differential->reviewableItems()),
                    'carried_forward_items' => count($differential->carriedForwardItems()),
                ],
            );

            return $locked->refresh();
        }, 3);
    }

    public function decide(
        CorrectionRequest $request,
        CorrectionResponse $response,
        CorrectionResponseReviewResult $result,
        string $reviewNotes,
        string $sourceFingerprint,
        ?string $expectedDecisionToken,
        User $actor,
    ): CorrectionResponse {
        return DB::transaction(function () use (
            $request,
            $response,
            $result,
            $reviewNotes,
            $sourceFingerprint,
            $expectedDecisionToken,
            $actor,
        ): CorrectionResponse {
            $lockedRequest = $this->lockedScopedRequest($request, $actor);
            $this->assertStarted($lockedRequest);

            if ($lockedRequest->revalidationBatch()->exists()) {
                throw ValidationException::withMessages([
                    'decision' => 'O lote já foi selado e as decisões são imutáveis.',
                ]);
            }

            $lockedResponse = CorrectionResponse::query()
                ->whereKey($response->id)
                ->where('correction_request_id', $lockedRequest->id)
                ->lockForUpdate()
                ->firstOrFail();
            $differential = $this->differentialResolver->resolve(
                $lockedRequest,
            );
            $item = collect($differential->reviewableItems())
                ->first(
                    static fn (CorrectionDifferentialItemData $candidate): bool => $candidate->correctionResponseId
                        === (int) $lockedResponse->id,
                );

            if (! $item instanceof CorrectionDifferentialItemData) {
                throw ValidationException::withMessages([
                    'decision' => 'O elemento não é reavaliável neste ciclo.',
                ]);
            }

            if ($differential->isStale() || $item->stale) {
                throw ValidationException::withMessages([
                    'decision' => $differential->blockers !== []
                        ? $differential->blockers
                        : ['A fonte do elemento foi alterada.'],
                ]);
            }

            if (! hash_equals($item->sourceFingerprint, $sourceFingerprint)) {
                throw ValidationException::withMessages([
                    'source_fingerprint' => 'A fonte do elemento foi alterada. Atualize a página antes de decidir.',
                ]);
            }

            $normalizedNotes = trim($reviewNotes);
            $sameDecision = $lockedResponse->review_result === $result
                && trim((string) $lockedResponse->review_notes) === $normalizedNotes
                && is_string($lockedResponse->decision_source_fingerprint)
                && hash_equals(
                    $item->sourceFingerprint,
                    $lockedResponse->decision_source_fingerprint,
                );

            if ($sameDecision) {
                return $lockedResponse;
            }

            if ($lockedResponse->review_result instanceof CorrectionResponseReviewResult) {
                $currentToken = $this->decisionToken($lockedResponse);

                if (
                    ! is_string($expectedDecisionToken)
                    || ! hash_equals($currentToken, $expectedDecisionToken)
                ) {
                    throw ValidationException::withMessages([
                        'decision' => 'A decisão foi alterada por outro utilizador. Atualize a página antes de continuar.',
                    ]);
                }
            } elseif ($expectedDecisionToken !== null) {
                throw ValidationException::withMessages([
                    'decision' => 'O estado da decisão já não corresponde à página apresentada.',
                ]);
            }

            [$responseStatus, $itemStatus] = match ($result) {
                CorrectionResponseReviewResult::Accepted => [
                    CorrectionResponseStatus::Accepted,
                    CorrectionRequestItemStatus::Accepted,
                ],
                CorrectionResponseReviewResult::Rejected => [
                    CorrectionResponseStatus::Rejected,
                    CorrectionRequestItemStatus::Rejected,
                ],
                CorrectionResponseReviewResult::NotApplicable => [
                    CorrectionResponseStatus::Accepted,
                    CorrectionRequestItemStatus::Waived,
                ],
                CorrectionResponseReviewResult::RequiresManualDecision => [
                    CorrectionResponseStatus::UnderReview,
                    CorrectionRequestItemStatus::Responded,
                ],
                CorrectionResponseReviewResult::RequiresMoreInformation => throw ValidationException::withMessages([
                    'result' => 'Este resultado pertence ao fluxo legado e não pode ser usado na segunda análise.',
                ]),
            };
            $reviewedAt = now('UTC');
            $lockedResponse->forceFill([
                'status' => $responseStatus,
                'review_result' => $result,
                'reviewed_by' => $actor->id,
                'reviewed_at' => $reviewedAt,
                'review_notes' => $normalizedNotes,
                'differential_classification' => $item->classification,
                'decision_source_fingerprint' => $item->sourceFingerprint,
            ])->save();
            $lockedResponse->correctionRequestItem()->update([
                'status' => $itemStatus->value,
            ]);

            $this->audit->record(
                event: AuditEvents::DECISION,
                auditable: $lockedResponse,
                module: 'administrative_processes',
                action: 'correction_item_reviewed',
                description: 'Elemento da segunda análise revisto.',
                newValues: [
                    'review_result' => $result->value,
                    'reviewed_at' => $reviewedAt->toIso8601String(),
                ],
                metadata: $this->auditContext($lockedRequest, $actor) + [
                    'correction_response_id' => (int) $lockedResponse->id,
                    'correction_request_item_id' => $item->correctionRequestItemId,
                    'classification' => $item->classification->value,
                    'source_fingerprint' => $item->sourceFingerprint,
                ],
            );

            return $lockedResponse->refresh();
        }, 3);
    }

    public function aggregateResult(
        CorrectionDifferentialResultData $differential,
        bool $throwWhenIncomplete = true,
    ): ?CorrectionRevalidationAggregateResult {
        if ($differential->isStale()) {
            if (! $throwWhenIncomplete) {
                return null;
            }

            throw ValidationException::withMessages([
                'revalidation' => $differential->blockers,
            ]);
        }

        $responses = $differential->request->responses->keyBy('id');
        $results = [];

        foreach ($differential->reviewableItems() as $item) {
            $response = $responses->get($item->correctionResponseId);

            if (
                ! $response instanceof CorrectionResponse
                || ! $this->hasCurrentDecision($response, $item)
            ) {
                if (! $throwWhenIncomplete) {
                    return null;
                }

                throw ValidationException::withMessages([
                    'revalidation' => 'Todos os elementos reavaliáveis devem possuir uma decisão atual antes da selagem.',
                ]);
            }

            $results[] = $response->review_result;
        }

        if (collect($results)->contains(
            static fn (mixed $result): bool => in_array($result, [
                CorrectionResponseReviewResult::RequiresManualDecision,
                CorrectionResponseReviewResult::RequiresMoreInformation,
            ], true),
        )) {
            return CorrectionRevalidationAggregateResult::RequiresManualDecision;
        }

        if (in_array(CorrectionResponseReviewResult::Rejected, $results, true)) {
            return CorrectionRevalidationAggregateResult::Rejected;
        }

        return CorrectionRevalidationAggregateResult::Accepted;
    }

    /**
     * @throws JsonException
     */
    public function decisionToken(CorrectionResponse $response): string
    {
        return hash_hmac(
            'sha256',
            $this->hasher->encode([
                'response_id' => (int) $response->id,
                'review_result' => $response->review_result?->value,
                'review_notes' => $response->review_notes,
                'reviewed_by' => $response->reviewed_by,
                'reviewed_at' => $response->reviewed_at?->toIso8601String(),
                'decision_source_fingerprint' => $response->decision_source_fingerprint,
            ]),
            (string) config('app.key'),
        );
    }

    /** @return Builder<CorrectionRequest> */
    private function queueQuery(User $actor): Builder
    {
        return $this->municipalScope
            ->correctionRequests(CorrectionRequest::query(), $actor)
            ->whereNotNull('application_review_publication_result_id')
            ->whereIn('status', self::QUEUE_STATUSES);
    }

    /**
     * @param  Builder<CorrectionRequest>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<CorrectionRequest>
     */
    private function applyQueueFilters(
        Builder $query,
        array $filters,
    ): Builder {
        $query = $query
            ->when(
                $filters['contest_id'] ?? null,
                static fn (Builder $requests, mixed $contestId): Builder => $requests
                    ->whereHas(
                        'publicationResult',
                        static fn (Builder $results): Builder => $results
                            ->where('contest_id', (int) $contestId),
                    ),
            )
            ->when(
                $filters['submitted_from'] ?? '',
                static fn (Builder $requests, mixed $from): Builder => $requests
                    ->whereDate('submitted_at', '>=', (string) $from),
            )
            ->when(
                $filters['submitted_to'] ?? '',
                static fn (Builder $requests, mixed $to): Builder => $requests
                    ->whereDate('submitted_at', '<=', (string) $to),
            )
            ->when(
                $filters['technician_id'] ?? null,
                static fn (Builder $requests, mixed $technicianId): Builder => $requests
                    ->where('revalidation_started_by', (int) $technicianId),
            )
            ->when(
                $filters['result'] ?? '',
                static fn (Builder $requests, mixed $result): Builder => $requests
                    ->where('revalidation_result', (string) $result),
            )
            ->when(
                $filters['process_number'] ?? '',
                static fn (Builder $requests, mixed $number): Builder => $requests
                    ->whereHas(
                        'administrativeProcess',
                        static fn (Builder $processes): Builder => $processes
                            ->where('process_number', 'like', '%'.addcslashes((string) $number, '%_\\').'%'),
                    ),
            )
            ->when(
                $filters['application_number'] ?? '',
                static fn (Builder $requests, mixed $number): Builder => $requests
                    ->whereHas(
                        'application',
                        static fn (Builder $applications): Builder => $applications
                            ->where('application_number', 'like', '%'.addcslashes((string) $number, '%_\\').'%'),
                    ),
            );

        return $this->applyStateFilter(
            $this->applySlaFilter(
                $query,
                (string) ($filters['sla'] ?? ''),
            ),
            (string) ($filters['state'] ?? ''),
        );
    }

    /**
     * @param  Builder<CorrectionRequest>  $query
     * @return Builder<CorrectionRequest>
     */
    private function applySlaFilter(Builder $query, string $sla): Builder
    {
        return match ($sla) {
            'overdue' => $query->where('response_deadline_at', '<', now()),
            'due_soon' => $query->whereBetween('response_deadline_at', [
                now(),
                now()->addHours(48),
            ]),
            'within_deadline' => $query->where('response_deadline_at', '>', now()->addHours(48)),
            default => $query,
        };
    }

    /**
     * @param  Builder<CorrectionRequest>  $query
     * @return Builder<CorrectionRequest>
     */
    private function applyStateFilter(Builder $query, string $state): Builder
    {
        return match ($state) {
            'awaiting_review' => $query->whereNull('revalidation_started_at'),
            'in_review' => $query
                ->whereNotNull('revalidation_started_at')
                ->whereDoesntHave('revalidationBatch')
                ->whereHas('responses', static fn (Builder $responses): Builder => $responses
                    ->whereNull('review_result')),
            'ready_to_seal' => $query
                ->whereNotNull('revalidation_started_at')
                ->whereDoesntHave('revalidationBatch')
                ->whereDoesntHave('responses', static fn (Builder $responses): Builder => $responses
                    ->whereNull('review_result'))
                ->whereDoesntHave('responses', static fn (Builder $responses): Builder => $responses
                    ->whereIn('review_result', [
                        CorrectionResponseReviewResult::RequiresManualDecision->value,
                        CorrectionResponseReviewResult::RequiresMoreInformation->value,
                    ])),
            'sealed' => $query
                ->whereHas('revalidationBatch')
                ->whereNull('revalidation_publication_result_id'),
            'published' => $query->whereNotNull(
                'revalidation_publication_result_id',
            ),
            'resolved' => $query->where(
                'status',
                CorrectionRequestStatus::Resolved->value,
            ),
            default => $query,
        };
    }

    /** @return array<string, int> */
    private function queueSummary(User $actor): array
    {
        $base = $this->queueQuery($actor);

        return [
            'total' => (clone $base)->count(),
            'awaiting_review' => (clone $base)
                ->whereNull('revalidation_started_at')
                ->count(),
            'in_review' => (clone $base)
                ->whereNotNull('revalidation_started_at')
                ->whereDoesntHave('revalidationBatch')
                ->count(),
            'sealed' => (clone $base)
                ->whereHas('revalidationBatch')
                ->whereNull('revalidation_publication_result_id')
                ->count(),
            'published' => (clone $base)
                ->whereNotNull('revalidation_publication_result_id')
                ->count(),
            'resolved' => (clone $base)
                ->where('status', CorrectionRequestStatus::Resolved->value)
                ->count(),
        ];
    }

    private function lockedScopedRequest(
        CorrectionRequest $request,
        User $actor,
    ): CorrectionRequest {
        return $this->municipalScope
            ->correctionRequests(CorrectionRequest::query(), $actor)
            ->whereKey($request->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertScope(
        CorrectionRequest $request,
        User $actor,
    ): void {
        if (! $this->municipalScope->ownsCorrectionRequest($actor, $request)) {
            abort(403);
        }
    }

    private function assertSubmittedCanonical(CorrectionRequest $request): void
    {
        if (
            $request->isLegacy()
            || $request->status !== CorrectionRequestStatus::Submitted
            || $request->submissionReceipt()->doesntExist()
        ) {
            throw ValidationException::withMessages([
                'revalidation' => 'Apenas um pedido canónico formalmente submetido pode entrar em segunda análise.',
            ]);
        }
    }

    private function assertStarted(CorrectionRequest $request): void
    {
        $this->assertSubmittedCanonical($request);

        if ($request->revalidation_started_at === null) {
            throw ValidationException::withMessages([
                'revalidation' => 'A segunda análise ainda não foi iniciada.',
            ]);
        }

        $process = $request->administrativeProcess()->first();

        if (
            ! $process instanceof AdministrativeProcess
            || $process->status !== AdministrativeProcessStatus::CorrectionUnderReview
        ) {
            throw ValidationException::withMessages([
                'revalidation' => 'O processo não se encontra em segunda análise.',
            ]);
        }
    }

    private function hasCurrentDecision(
        CorrectionResponse $response,
        CorrectionDifferentialItemData $item,
    ): bool {
        return $response->review_result instanceof CorrectionResponseReviewResult
            && $response->reviewed_at !== null
            && $response->reviewed_by !== null
            && is_string($response->decision_source_fingerprint)
            && hash_equals(
                $item->sourceFingerprint,
                $response->decision_source_fingerprint,
            );
    }

    /** @return array<string, int|null> */
    private function auditContext(
        CorrectionRequest $request,
        User $actor,
    ): array {
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
