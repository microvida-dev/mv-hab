<?php

namespace App\Services\BackofficeDashboard;

use App\Enums\InternalAlertStatus;
use App\Enums\TicketStatus;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\InternalAlert;
use App\Models\SupportTicket;
use App\Models\User;

class OperationalDashboardService
{
    public function __construct(private readonly DashboardMetricAggregator $aggregator) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(array $filters, User $actor): array
    {
        return [
            'metrics' => $this->aggregator->aggregate($filters, $actor),
            'work_queue' => [
                'unassigned_applications' => Application::query()
                    ->whereDoesntHave('administrativeProcess')
                    ->latest()
                    ->limit(10)
                    ->get(['id', 'public_id', 'application_number', 'status', 'contest_id', 'user_id']),
                'documents_pending' => DocumentSubmission::query()
                    ->with(['application', 'user', 'documentType'])
                    ->whereIn('status', ['submitted', 'under_review'])
                    ->latest()
                    ->limit(10)
                    ->get(),
                'visits_week' => app(VisitStatisticsService::class)->summary($filters),
                'tickets_pending' => SupportTicket::query()
                    ->whereIn('status', [TicketStatus::Open->value, TicketStatus::PendingStaff->value, TicketStatus::InProgress->value])
                    ->latest()
                    ->limit(10)
                    ->get(),
                'alerts_assigned' => InternalAlert::query()
                    ->where('assigned_to', $actor->id)
                    ->whereIn('status', [InternalAlertStatus::Open->value, InternalAlertStatus::Seen->value, InternalAlertStatus::InProgress->value])
                    ->latest()
                    ->limit(10)
                    ->get(),
            ],
            'generated_at' => now(),
        ];
    }
}
