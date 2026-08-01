<?php

namespace App\Services\Administrative;

use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionRevalidationAggregateResult;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestItem;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CorrectionProgressMetricsService
{
    /** @var list<string> */
    private const ACTIVE_STATUSES = [
        CorrectionRequestStatus::Notified->value,
        CorrectionRequestStatus::Open->value,
        CorrectionRequestStatus::PartiallyCompleted->value,
    ];

    /** @var list<string> */
    private const COMPLETED_ITEM_STATUSES = [
        CorrectionRequestItemStatus::Responded->value,
        CorrectionRequestItemStatus::Accepted->value,
        CorrectionRequestItemStatus::Waived->value,
    ];

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /**
     * @return array{
     *     total: int,
     *     completed: int,
     *     pending: int,
     *     percentage: int,
     *     ready_for_submission: bool,
     *     formal_submitted: bool,
     *     overdue: bool,
     *     due_soon: bool
     * }
     */
    public function progress(CorrectionRequest $request): array
    {
        /** @var Collection<int, CorrectionRequestItem> $items */
        $items = $request->relationLoaded('items')
            ? $request->getRelation('items')
            : $request->items()
                ->get([
                    'id',
                    'correction_request_id',
                    'status',
                    'is_required',
                    'sort_order',
                ]);

        $required = $items
            ->filter(
                static fn (CorrectionRequestItem $item): bool => $item->is_required
                    && $item->status
                        !== CorrectionRequestItemStatus::Cancelled,
            )
            ->values();

        $completed = $required
            ->filter(
                static fn (CorrectionRequestItem $item): bool => in_array(
                    $item->status->value,
                    self::COMPLETED_ITEM_STATUSES,
                    true,
                ),
            )
            ->count();

        $total = $required->count();
        $pending = max(0, $total - $completed);
        $deadline = $request->effectiveDeadline();
        $acceptsWork = $request->status->acceptsCandidateWork();
        $overdue = $request->status === CorrectionRequestStatus::Expired
            || (
                $acceptsWork
                && $deadline?->isPast() === true
            );
        $dueSoon = $acceptsWork
            && $deadline !== null
            && ! $deadline->isPast()
            && $deadline->lessThanOrEqualTo(now()->addHours(48));

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'percentage' => $total === 0
                ? 100
                : (int) round(($completed / $total) * 100),
            'ready_for_submission' => $total > 0 && $pending === 0,
            'formal_submitted' => $request->submitted_at !== null
                || $request->status
                    === CorrectionRequestStatus::Submitted,
            'overdue' => $overdue,
            'due_soon' => $dueSoon,
        ];
    }

    /**
     * @param  Collection<int, CorrectionRequest>  $requests
     * @return array<int, array{
     *     total: int,
     *     completed: int,
     *     pending: int,
     *     percentage: int,
     *     ready_for_submission: bool,
     *     formal_submitted: bool,
     *     overdue: bool,
     *     due_soon: bool
     * }>
     */
    public function forRequests(Collection $requests): array
    {
        return $requests
            ->mapWithKeys(
                fn (CorrectionRequest $request): array => [
                    (int) $request->getKey() => $this->progress($request),
                ],
            )
            ->all();
    }

    /**
     * @param  Collection<int, CorrectionRequest>  $requests
     * @return array<string, int>
     */
    public function summarize(Collection $requests): array
    {
        $progress = collect($this->forRequests($requests));
        $statuses = $requests->countBy(
            static fn (CorrectionRequest $request): string => $request->status->value,
        );

        $totalItems = (int) $progress->sum('total');
        $completedItems = (int) $progress->sum('completed');

        return [
            'total_requests' => $requests->count(),
            'active_requests' => $this->countStatuses(
                $statuses,
                self::ACTIVE_STATUSES,
            ),
            'partially_completed_requests' => (int) $statuses->get(
                CorrectionRequestStatus::PartiallyCompleted->value,
                0,
            ),
            'submitted_requests' => (int) $statuses->get(
                CorrectionRequestStatus::Submitted->value,
                0,
            ),
            'expired_requests' => (int) $statuses->get(
                CorrectionRequestStatus::Expired->value,
                0,
            ),
            'resolved_requests' => (int) $statuses->get(
                CorrectionRequestStatus::Resolved->value,
                0,
            ),
            'cancelled_requests' => (int) $statuses->get(
                CorrectionRequestStatus::Cancelled->value,
                0,
            ),
            'due_soon_requests' => $progress->where('due_soon', true)->count(),
            'overdue_requests' => $progress->where('overdue', true)->count(),
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'pending_items' => max(0, $totalItems - $completedItems),
            'percentage' => $totalItems === 0
                ? 100
                : (int) round(
                    ($completedItems / $totalItems) * 100,
                ),
        ];
    }

    /**
     * @return array{
     *     available: bool,
     *     summary: array<string, int|bool|null>,
     *     urgent: list<array<string, mixed>>
     * }
     */
    public function municipalDashboard(User $user): array
    {
        if (
            $user->hasRole('candidate')
            || ! $user->hasPermission(
                'administrative_processes.view',
            )
        ) {
            return $this->emptyDashboard();
        }

        $statusCounts = $this->scopedRequests($user)
            ->toBase()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(
                static fn (mixed $value): int => (int) $value,
            );

        $requiredItems = CorrectionRequestItem::query()
            ->whereIn(
                'correction_request_id',
                $this->scopedRequests($user)
                    ->select('correction_requests.id'),
            )
            ->where('is_required', true)
            ->where(
                'status',
                '!=',
                CorrectionRequestItemStatus::Cancelled->value,
            );

        $totalItems = (clone $requiredItems)->count();
        $completedItems = (clone $requiredItems)
            ->whereIn('status', self::COMPLETED_ITEM_STATUSES)
            ->count();

        $active = $this->countStatuses(
            $statusCounts,
            self::ACTIVE_STATUSES,
        );

        $dueSoon = $this->scopedRequests($user)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereBetween('response_deadline_at', [
                now(),
                now()->addHours(48),
            ])
            ->count();

        $overdue = $this->scopedRequests($user)
            ->where(function (Builder $query): void {
                $query
                    ->where(
                        'status',
                        CorrectionRequestStatus::Expired->value,
                    )
                    ->orWhere(function (Builder $active): void {
                        $active
                            ->whereIn(
                                'status',
                                self::ACTIVE_STATUSES,
                            )
                            ->where(
                                'response_deadline_at',
                                '<',
                                now(),
                            );
                    });
            })
            ->count();
        $revalidation = $this->revalidationSummary($user);

        $summary = [
            'total_requests' => (int) $statusCounts->sum(),
            'active_requests' => $active,
            'partially_completed_requests' => (int) $statusCounts->get(
                CorrectionRequestStatus::PartiallyCompleted->value,
                0,
            ),
            'submitted_requests' => (int) $statusCounts->get(
                CorrectionRequestStatus::Submitted->value,
                0,
            ),
            'expired_requests' => (int) $statusCounts->get(
                CorrectionRequestStatus::Expired->value,
                0,
            ),
            'resolved_requests' => (int) $statusCounts->get(
                CorrectionRequestStatus::Resolved->value,
                0,
            ),
            'cancelled_requests' => (int) $statusCounts->get(
                CorrectionRequestStatus::Cancelled->value,
                0,
            ),
            'due_soon_requests' => $dueSoon,
            'overdue_requests' => $overdue,
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'pending_items' => max(
                0,
                $totalItems - $completedItems,
            ),
            'percentage' => $totalItems === 0
                ? 100
                : (int) round(
                    ($completedItems / $totalItems) * 100,
                ),
            ...$revalidation,
        ];

        /** @var list<array<string, mixed>> $urgent */
        $urgent = $this->scopedRequests($user)
            ->with('items:id,correction_request_id,status,is_required,sort_order')
            ->whereIn('status', [
                CorrectionRequestStatus::Expired->value,
                CorrectionRequestStatus::Submitted->value,
                CorrectionRequestStatus::PartiallyCompleted->value,
                CorrectionRequestStatus::Open->value,
                CorrectionRequestStatus::Notified->value,
            ])
            ->orderByRaw(
                'CASE status '
                .'WHEN ? THEN 0 '
                .'WHEN ? THEN 1 '
                .'WHEN ? THEN 2 '
                .'WHEN ? THEN 3 '
                .'WHEN ? THEN 4 '
                .'ELSE 9 END',
                [
                    CorrectionRequestStatus::Expired->value,
                    CorrectionRequestStatus::Submitted->value,
                    CorrectionRequestStatus::PartiallyCompleted->value,
                    CorrectionRequestStatus::Open->value,
                    CorrectionRequestStatus::Notified->value,
                ],
            )
            ->orderByRaw(
                'CASE WHEN response_deadline_at IS NULL '
                .'THEN 1 ELSE 0 END',
            )
            ->orderBy('response_deadline_at')
            ->limit(6)
            ->get()
            ->map(
                fn (CorrectionRequest $request): array => $this->operationalItem($request),
            )
            ->values()
            ->all();

        return [
            'available' => true,
            'summary' => $summary,
            'urgent' => $urgent,
        ];
    }

    /**
     * @return Builder<CorrectionRequest>
     */
    private function scopedRequests(User $user): Builder
    {
        return $this->municipalScope->correctionRequests(
            CorrectionRequest::query(),
            $user,
        );
    }

    /**
     * @return array{
     *     submitted_for_revalidation: int,
     *     partially_reviewed_revalidations: int,
     *     ready_to_close_revalidations: int,
     *     sealed_revalidations: int,
     *     published_revalidations: int,
     *     resolved_revalidations: int,
     *     rejected_revalidations: int,
     *     revalidation_sla_configured: bool,
     *     revalidation_sla_overdue_requests: null,
     *     average_revalidation_minutes: int
     * }
     */
    private function revalidationSummary(User $user): array
    {
        $submitted = $this->scopedRequests($user)
            ->where('status', CorrectionRequestStatus::Submitted->value);
        $inReview = (clone $submitted)
            ->whereNotNull('revalidation_started_at')
            ->whereDoesntHave('revalidationBatch');
        $partiallyReviewed = (clone $inReview)
            ->whereHas(
                'responses',
                static fn (Builder $responses): Builder => $responses
                    ->whereNotNull('reviewed_at'),
            )
            ->whereHas(
                'responses',
                static fn (Builder $responses): Builder => $responses
                    ->whereNull('review_result'),
            )
            ->count();
        $readyToClose = (clone $inReview)
            ->whereHas('responses')
            ->whereDoesntHave(
                'responses',
                static fn (Builder $responses): Builder => $responses
                    ->whereNull('review_result')
                    ->orWhere(
                        'review_result',
                        CorrectionResponseReviewResult::RequiresManualDecision->value,
                    ),
            )
            ->count();

        return [
            'submitted_for_revalidation' => (clone $submitted)
                ->whereNull('revalidation_started_at')
                ->count(),
            'partially_reviewed_revalidations' => $partiallyReviewed,
            'ready_to_close_revalidations' => $readyToClose,
            'sealed_revalidations' => $this->scopedRequests($user)
                ->whereHas('revalidationBatch')
                ->count(),
            'published_revalidations' => $this->scopedRequests($user)
                ->whereHas('revalidationBatch.publication')
                ->count(),
            'resolved_revalidations' => $this->scopedRequests($user)
                ->where('status', CorrectionRequestStatus::Resolved->value)
                ->whereNotNull('revalidation_projected_at')
                ->count(),
            'rejected_revalidations' => $this->scopedRequests($user)
                ->where(
                    'revalidation_result',
                    CorrectionRevalidationAggregateResult::Rejected->value,
                )
                ->count(),
            'revalidation_sla_configured' => false,
            'revalidation_sla_overdue_requests' => null,
            'average_revalidation_minutes' => $this->averageRevalidationMinutes(
                $user,
            ),
        ];
    }

    private function averageRevalidationMinutes(User $user): int
    {
        $query = $this->scopedRequests($user)
            ->whereNotNull('revalidation_started_at')
            ->whereNotNull('revalidation_projected_at');
        $expression = match (DB::getDriverName()) {
            'mysql', 'mariadb' => 'AVG(TIMESTAMPDIFF(SECOND, revalidation_started_at, revalidation_projected_at) / 60)',
            'pgsql' => 'AVG(EXTRACT(EPOCH FROM (revalidation_projected_at - revalidation_started_at)) / 60)',
            'sqlite' => 'AVG((julianday(revalidation_projected_at) - julianday(revalidation_started_at)) * 1440)',
            default => null,
        };

        if ($expression === null) {
            return 0;
        }

        $average = $query
            ->selectRaw($expression.' AS aggregate')
            ->value('aggregate');

        return max(0, (int) round((float) ($average ?? 0)));
    }

    /**
     * @param  Collection<array-key, int>  $counts
     * @param  list<string>  $statuses
     */
    private function countStatuses(
        Collection $counts,
        array $statuses,
    ): int {
        return (int) collect($statuses)->sum(
            static fn (string $status): int => (int) $counts->get($status, 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function operationalItem(
        CorrectionRequest $request,
    ): array {
        $progress = $this->progress($request);

        return [
            'request_number' => $request->request_number,
            'status' => $request->status->value,
            'status_label' => $request->status->label(),
            'deadline' => $request->response_deadline_at?->toIso8601String(),
            'submitted_at' => $request->submitted_at?->toIso8601String(),
            'completed' => $progress['completed'],
            'total' => $progress['total'],
            'pending' => $progress['pending'],
            'percentage' => $progress['percentage'],
            'overdue' => $progress['overdue'],
            'due_soon' => $progress['due_soon'],
            'tone' => $this->tone($request, $progress),
            'route' => route(
                'backoffice.correction-requests.show',
                $request,
            ),
        ];
    }

    /**
     * @param  array{
     *     overdue: bool,
     *     due_soon: bool,
     *     formal_submitted: bool,
     *     total: int,
     *     completed: int,
     *     pending: int,
     *     percentage: int,
     *     ready_for_submission: bool
     * }  $progress
     */
    private function tone(
        CorrectionRequest $request,
        array $progress,
    ): string {
        if ($progress['overdue']) {
            return 'danger';
        }

        if (
            $request->status
                === CorrectionRequestStatus::Submitted
        ) {
            return 'info';
        }

        if ($progress['due_soon']) {
            return 'warning';
        }

        return 'neutral';
    }

    /**
     * @return array{
     *     available: false,
     *     summary: array<string, int|bool|null>,
     *     urgent: array{}
     * }
     */
    private function emptyDashboard(): array
    {
        return [
            'available' => false,
            'summary' => [
                'total_requests' => 0,
                'active_requests' => 0,
                'partially_completed_requests' => 0,
                'submitted_requests' => 0,
                'expired_requests' => 0,
                'resolved_requests' => 0,
                'cancelled_requests' => 0,
                'due_soon_requests' => 0,
                'overdue_requests' => 0,
                'total_items' => 0,
                'completed_items' => 0,
                'pending_items' => 0,
                'percentage' => 100,
                'submitted_for_revalidation' => 0,
                'partially_reviewed_revalidations' => 0,
                'ready_to_close_revalidations' => 0,
                'sealed_revalidations' => 0,
                'published_revalidations' => 0,
                'resolved_revalidations' => 0,
                'rejected_revalidations' => 0,
                'revalidation_sla_configured' => false,
                'revalidation_sla_overdue_requests' => null,
                'average_revalidation_minutes' => 0,
            ],
            'urgent' => [],
        ];
    }
}
