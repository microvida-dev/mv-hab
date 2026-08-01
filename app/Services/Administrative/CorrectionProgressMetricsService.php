<?php

namespace App\Services\Administrative;

use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequestStatus;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestItem;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
     *     summary: array<string, int>,
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
     *     summary: array<string, int>,
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
            ],
            'urgent' => [],
        ];
    }
}
