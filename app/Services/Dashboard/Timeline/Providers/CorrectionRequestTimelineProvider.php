<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionRevalidationAggregateResult;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\CorrectionRequest;
use App\Models\User;
use App\Services\Administrative\CorrectionProgressMetricsService;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineEventFactory;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

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
                ->with([
                    'items:id,correction_request_id,status,is_required,sort_order',
                    'revalidationBatch:id,correction_request_id,sealed_at',
                    'revalidationBatch.publication:id,application_review_batch_id,published_at',
                ])
                ->withCount([
                    'responses as revalidation_responses_count',
                    'responses as revalidation_reviewed_responses_count' => static fn (Builder $responses): Builder => $responses
                        ->whereNotNull('reviewed_at'),
                    'responses as revalidation_manual_responses_count' => static fn (Builder $responses): Builder => $responses
                        ->where(
                            'review_result',
                            CorrectionResponseReviewResult::RequiresManualDecision->value,
                        ),
                ])
                ->whereIn('status', [
                    CorrectionRequestStatus::Notified->value,
                    CorrectionRequestStatus::Open->value,
                    CorrectionRequestStatus::PartiallyCompleted->value,
                    CorrectionRequestStatus::Submitted->value,
                    CorrectionRequestStatus::Expired->value,
                    CorrectionRequestStatus::Resolved->value,
                ])
                ->orderByRaw(
                    'CASE status '
                    .'WHEN ? THEN 0 '
                    .'WHEN ? THEN 1 '
                    .'WHEN ? THEN 2 '
                    .'WHEN ? THEN 3 '
                    .'WHEN ? THEN 4 '
                    .'WHEN ? THEN 5 '
                    .'ELSE 9 END',
                    [
                        CorrectionRequestStatus::Expired->value,
                        CorrectionRequestStatus::Submitted->value,
                        CorrectionRequestStatus::PartiallyCompleted->value,
                        CorrectionRequestStatus::Open->value,
                        CorrectionRequestStatus::Notified->value,
                        CorrectionRequestStatus::Resolved->value,
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
        $stage = $this->revalidationStage($request);
        $submitted = in_array($stage, [
            'submitted',
            'started',
            'ready',
            'sealed',
            'published',
            'resolved',
            'rejected',
        ], true);

        return $this->factory->make(
            id: 'correction-request-'.$request->getKey(),
            type: $submitted
                ? TimelineType::CorrectionResponse
                : TimelineType::CorrectionRequest,
            title: $this->title($request, $stage),
            description: $this->description(
                $request,
                $progress,
            ),
            route: route(
                'backoffice.correction-requests.show',
                $request,
            ),
            datetime: $this->eventDate($request, $stage),
            priority: $this->priority($request, $progress, $stage),
            icon: $submitted
                ? 'document-check'
                : 'document-warning',
            tone: $this->eventTone($request, $progress, $stage),
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
                'revalidation_stage' => $stage,
                'revalidation_result' => $request->revalidation_result?->value,
                'revalidation_responses' => (int) $request->getAttribute(
                    'revalidation_responses_count',
                ),
                'revalidation_reviewed' => (int) $request->getAttribute(
                    'revalidation_reviewed_responses_count',
                ),
            ],
        );
    }

    private function title(
        CorrectionRequest $request,
        string $stage,
    ): string {
        if ($stage === 'rejected') {
            return 'Aperfeiçoamento não aceite';
        }

        if ($stage === 'resolved') {
            return 'Segunda análise concluída';
        }

        if ($stage === 'published') {
            return 'Resultado da segunda análise publicado';
        }

        if ($stage === 'sealed') {
            return 'Segunda análise selada';
        }

        if ($stage === 'ready') {
            return 'Segunda análise pronta para fecho';
        }

        if ($stage === 'started') {
            return 'Segunda análise em curso';
        }

        return match ($request->status) {
            CorrectionRequestStatus::Submitted => 'Aperfeiçoamento submetido para segunda análise',
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
        string $stage,
    ): TimelinePriority {
        if (
            $request->status === CorrectionRequestStatus::Expired
            || $progress['overdue']
            || $progress['due_soon']
        ) {
            return TimelinePriority::Critical;
        }

        if (
            in_array($stage, ['ready', 'sealed'], true)
            ||
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
        string $stage,
    ): string {
        if ($stage === 'rejected') {
            return 'danger';
        }

        if ($stage === 'resolved') {
            return 'success';
        }

        if (in_array($stage, ['ready', 'sealed', 'published'], true)) {
            return 'info';
        }

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

    private function revalidationStage(CorrectionRequest $request): string
    {
        if (
            $request->status === CorrectionRequestStatus::Resolved
            && $request->revalidation_projected_at !== null
        ) {
            return $request->revalidation_result
                === CorrectionRevalidationAggregateResult::Rejected
                    ? 'rejected'
                    : 'resolved';
        }

        if ($request->revalidationBatch?->publication !== null) {
            return 'published';
        }

        if ($request->revalidationBatch !== null) {
            return 'sealed';
        }

        if ($request->revalidation_started_at !== null) {
            $responses = (int) $request->getAttribute(
                'revalidation_responses_count',
            );
            $reviewed = (int) $request->getAttribute(
                'revalidation_reviewed_responses_count',
            );
            $manual = (int) $request->getAttribute(
                'revalidation_manual_responses_count',
            );

            return $responses > 0
                && $reviewed >= $responses
                && $manual === 0
                    ? 'ready'
                    : 'started';
        }

        return $request->status === CorrectionRequestStatus::Submitted
            ? 'submitted'
            : 'candidate_response';
    }

    private function eventDate(
        CorrectionRequest $request,
        string $stage,
    ): ?Carbon {
        return match ($stage) {
            'resolved', 'rejected' => $request->revalidation_projected_at,
            'published' => $request->revalidationBatch?->publication?->published_at,
            'sealed' => $request->revalidationBatch?->sealed_at,
            'started', 'ready' => $request->revalidation_started_at,
            'submitted' => $request->submitted_at,
            default => $request->expired_at ?? $request->response_deadline_at,
        };
    }
}
