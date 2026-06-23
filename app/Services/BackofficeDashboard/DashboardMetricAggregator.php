<?php

namespace App\Services\BackofficeDashboard;

use App\Enums\DocumentStatus;
use App\Enums\InternalAlertSeverity;
use App\Enums\InternalAlertStatus;
use App\Enums\TicketStatus;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\InternalAlert;
use App\Models\ProcedureMinute;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardMetricAggregator
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function aggregate(array $filters, User $actor): array
    {
        $applications = $this->applicationQuery($filters);

        return [
            'generated_at' => now()->toIso8601String(),
            'filters' => $this->publicFilters($filters),
            'applications' => [
                'total' => (clone $applications)->count(),
                'submitted_today' => (clone $applications)->whereDate('submitted_at', today())->count(),
                'submitted_week' => (clone $applications)->where('submitted_at', '>=', now()->startOfWeek())->count(),
                'submitted_month' => (clone $applications)->where('submitted_at', '>=', now()->startOfMonth())->count(),
                'by_status' => $this->groupedCounts((clone $applications), 'status'),
                'by_contest' => $this->applicationsByContest($filters),
                'pending_action' => (clone $applications)->whereIn('status', ['submitted', 'under_review', 'requires_correction'])->count(),
            ],
            'documents' => [
                'pending' => $this->documentCount($filters, [DocumentStatus::Submitted->value, DocumentStatus::UnderReview->value]),
                'rejected' => $this->documentCount($filters, [DocumentStatus::Rejected->value]),
                'validated' => $this->documentCount($filters, [DocumentStatus::Validated->value]),
                'expired' => $this->documentCount($filters, [DocumentStatus::Expired->value]),
            ],
            'visits' => app(VisitStatisticsService::class)->summary($filters),
            'tickets' => [
                'open' => SupportTicket::query()->whereIn('status', [
                    TicketStatus::Open->value,
                    TicketStatus::PendingStaff->value,
                    TicketStatus::InProgress->value,
                    TicketStatus::Reopened->value,
                ])->count(),
            ],
            'deadlines' => app(DeadlineStatisticsService::class)->summary($filters),
            'alerts' => [
                'open' => InternalAlert::query()->whereIn('status', [
                    InternalAlertStatus::Open->value,
                    InternalAlertStatus::Seen->value,
                    InternalAlertStatus::InProgress->value,
                ])->count(),
                'critical' => InternalAlert::query()
                    ->where('severity', InternalAlertSeverity::Critical->value)
                    ->whereIn('status', [InternalAlertStatus::Open->value, InternalAlertStatus::Seen->value, InternalAlertStatus::InProgress->value])
                    ->count(),
                'assigned_to_me' => InternalAlert::query()
                    ->where('assigned_to', $actor->id)
                    ->whereIn('status', [InternalAlertStatus::Open->value, InternalAlertStatus::Seen->value, InternalAlertStatus::InProgress->value])
                    ->count(),
            ],
            'lists' => [
                'provisional_pending' => DB::table('provisional_lists')->whereIn('status', ['draft', 'under_review'])->count(),
                'definitive_pending' => DB::table('definitive_lists')->whereIn('status', ['draft', 'under_review'])->count(),
            ],
            'reports' => [
                'application_reports' => DB::table('application_reports')->count(),
            ],
            'minutes' => [
                'pending_review' => ProcedureMinute::query()->whereIn('status', ['draft', 'generated', 'under_review'])->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Application>
     */
    public function applicationQuery(array $filters): Builder
    {
        $query = Application::query();

        if (! empty($filters['program_id'])) {
            $query->where('program_id', (int) $filters['program_id']);
        }

        if (! empty($filters['contest_id'])) {
            $query->where('contest_id', (int) $filters['contest_id']);
        }

        if (! empty($filters['period_start'])) {
            $query->whereDate('created_at', '>=', Carbon::parse((string) $filters['period_start'])->toDateString());
        }

        if (! empty($filters['period_end'])) {
            $query->whereDate('created_at', '<=', Carbon::parse((string) $filters['period_end'])->toDateString());
        }

        return $query;
    }

    /**
     * @param  Builder<Application>  $query
     * @return array<string, int>
     */
    private function groupedCounts(Builder $query, string $column): array
    {
        return $query
            ->select($column, DB::raw('count(*) as aggregate'))
            ->groupBy($column)
            ->pluck('aggregate', $column)
            ->map(fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{contest_id:int|null,title:string,total:int}>
     */
    private function applicationsByContest(array $filters): array
    {
        return $this->applicationQuery($filters)
            ->leftJoin('contests', 'applications.contest_id', '=', 'contests.id')
            ->select('applications.contest_id', DB::raw('coalesce(contests.title, "Sem concurso") as title'), DB::raw('count(*) as total'))
            ->groupBy('applications.contest_id', 'contests.title')
            ->orderByDesc('total')
            ->limit(10)
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'contest_id' => $row->contest_id !== null ? (int) $row->contest_id : null,
                'title' => (string) $row->title,
                'total' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $statuses
     */
    private function documentCount(array $filters, array $statuses): int
    {
        $query = DocumentSubmission::query()
            ->whereIn('status', $statuses)
            ->whereNotNull('application_id');

        if (! empty($filters['contest_id'])) {
            $query->whereHas('application', fn (Builder $builder) => $builder->where('contest_id', (int) $filters['contest_id']));
        }

        if (! empty($filters['program_id'])) {
            $query->whereHas('application', fn (Builder $builder) => $builder->where('program_id', (int) $filters['program_id']));
        }

        return $query->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function publicFilters(array $filters): array
    {
        return array_filter([
            'program_id' => $filters['program_id'] ?? null,
            'contest_id' => $filters['contest_id'] ?? null,
            'period_start' => $filters['period_start'] ?? null,
            'period_end' => $filters['period_end'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
