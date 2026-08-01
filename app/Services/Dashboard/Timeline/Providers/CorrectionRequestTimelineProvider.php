<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\CorrectionRequestStatus;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\CorrectionRequest;
use App\Models\User;
use App\Services\Administrative\CorrectionProgressMetricsService;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineEventFactory;
use App\Services\Municipalities\MunicipalRecordScopeService;

class CorrectionRequestTimelineProvider extends BaseTimelineProvider
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly CorrectionProgressMetricsService $progress,
        private readonly TimelineEventFactory $factory,
    ) {}

    public function forUser(
        User $user,
        array $dashboard = [],
    ): array {
        if (
            $user->hasRole('candidate')
            || ! $user->hasPermission(
                'administrative_processes.view',
            )
        ) {
            return [];
        }

        return array_values(
            $this->municipalScope
                ->correctionRequests(
                    CorrectionRequest::query(),
                    $user,
                )
                ->with('items:id,correction_request_id,status,is_required,sort_order')
                ->whereIn('status', [
                    CorrectionRequestStatus::Notified->value,
                    CorrectionRequestStatus::Open->value,
                    CorrectionRequestStatus::PartiallyCompleted->value,
                    CorrectionRequestStatus::Submitted->value,
                    CorrectionRequestStatus::Expired->value,
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
                ->orderBy('response_deadline_at')
                ->limit(40)
                ->get()
                ->map(
                    fn (CorrectionRequest $request): TimelineEvent => $this->event($request),
                )
                ->values()
                ->all(),
        );
    }

    private function event(
        CorrectionRequest $request,
    ): TimelineEvent {
        $progress = $this->progress->progress($request);
        $submitted =
            $request->status
                === CorrectionRequestStatus::Submitted;

        return $this->factory->make(
            id: 'correction-request-'.$request->getKey(),
            type: $submitted
                ? TimelineType::CorrectionResponse
                : TimelineType::CorrectionRequest,
            title: $this->title($request),
            description: $this->description(
                $request,
                $progress,
            ),
            route: route(
                'backoffice.correction-requests.show',
                $request,
            ),
            datetime: $submitted
                ? $request->submitted_at
                : (
                    $request->expired_at
                    ?? $request->response_deadline_at
                ),
            priority: $this->priority($request, $progress),
            icon: $submitted
                ? 'document-check'
                : 'document-warning',
            tone: $this->eventTone($request, $progress),
            workspace: TimelineWorkspace::Applications,
            metadata: [
                'correction_request_id' => $request->getKey(),
                'request_number' => $request->request_number,
                'status' => $request->status->value,
                'completed_items' => $progress['completed'],
                'total_items' => $progress['total'],
                'pending_items' => $progress['pending'],
                'progress_percentage' => $progress['percentage'],
                'formal_submitted' => $progress['formal_submitted'],
                'overdue' => $progress['overdue'],
                'due_soon' => $progress['due_soon'],
            ],
        );
    }

    private function title(
        CorrectionRequest $request,
    ): string {
        return match ($request->status) {
            CorrectionRequestStatus::Submitted => 'Aperfeiçoamento formalmente submetido',
            CorrectionRequestStatus::Expired => 'Prazo de aperfeiçoamento expirado',
            CorrectionRequestStatus::PartiallyCompleted => 'Aperfeiçoamento parcialmente preparado',
            CorrectionRequestStatus::Open,
            CorrectionRequestStatus::Notified => 'Pedido de aperfeiçoamento pendente',
            default => 'Pedido de aperfeiçoamento',
        };
    }

    /**
     * @param  array{
     *     total: int,
     *     completed: int,
     *     pending: int,
     *     percentage: int,
     *     ready_for_submission: bool,
     *     formal_submitted: bool,
     *     overdue: bool,
     *     due_soon: bool
     * }  $progress
     */
    private function description(
        CorrectionRequest $request,
        array $progress,
    ): string {
        return $this->concat(
            $request->request_number,
            $progress['completed'].'/'.$progress['total']
                .' elementos preparados',
            $request->status->label(),
        );
    }

    /**
     * @param  array{
     *     overdue: bool,
     *     due_soon: bool,
     *     total: int,
     *     completed: int,
     *     pending: int,
     *     percentage: int,
     *     ready_for_submission: bool,
     *     formal_submitted: bool
     * }  $progress
     */
    private function priority(
        CorrectionRequest $request,
        array $progress,
    ): TimelinePriority {
        if (
            $request->status === CorrectionRequestStatus::Expired
            || $progress['overdue']
            || $progress['due_soon']
        ) {
            return TimelinePriority::Critical;
        }

        if (
            $request->status
                === CorrectionRequestStatus::Submitted
            || $request->status
                === CorrectionRequestStatus::PartiallyCompleted
        ) {
            return TimelinePriority::High;
        }

        return TimelinePriority::Medium;
    }

    /**
     * @param  array{
     *     overdue: bool,
     *     due_soon: bool,
     *     total: int,
     *     completed: int,
     *     pending: int,
     *     percentage: int,
     *     ready_for_submission: bool,
     *     formal_submitted: bool
     * }  $progress
     */
    private function eventTone(
        CorrectionRequest $request,
        array $progress,
    ): string {
        if (
            $request->status === CorrectionRequestStatus::Expired
            || $progress['overdue']
        ) {
            return 'danger';
        }

        if (
            $request->status
                === CorrectionRequestStatus::Submitted
        ) {
            return 'info';
        }

        return $progress['due_soon']
            ? 'warning'
            : 'neutral';
    }
}
