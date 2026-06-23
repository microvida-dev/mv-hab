<?php

namespace App\Services\Reporting\Indicators;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Services\Reporting\ReportFilterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ApplicationIndicatorsService
{
    public function __construct(private readonly ReportFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Application>
     */
    private function query(array $filters): Builder
    {
        return $this->filters->applyApplication(Application::query(), $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int|string, mixed>
     */
    public function countApplicationsByContest(array $filters): array
    {
        return $this->query($filters)->select('contest_id', DB::raw('COUNT(*) as total'))->groupBy('contest_id')->pluck('total', 'contest_id')->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int|string, mixed>
     */
    public function countApplicationsByProgram(array $filters): array
    {
        return $this->query($filters)->select('program_id', DB::raw('COUNT(*) as total'))->groupBy('program_id')->pluck('total', 'program_id')->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int|string, mixed>
     */
    public function countApplicationsByStatus(array $filters): array
    {
        return $this->query($filters)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status')->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countSubmittedApplications(array $filters): int
    {
        return $this->query($filters)->whereNotNull('submitted_at')->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countEligibleApplications(array $filters): int
    {
        return $this->query($filters)->where('status', ApplicationStatus::Eligible->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countExcludedApplications(array $filters): int
    {
        return $this->query($filters)->whereIn('status', [ApplicationStatus::Excluded->value, ApplicationStatus::Ineligible->value])->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function averageAnalysisTime(array $filters): float
    {
        return $this->averageDays($filters, 'administrative_processes.received_at', 'administrative_processes.updated_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function averageSubmissionToDecisionTime(array $filters): float
    {
        return $this->averageDays($filters, 'applications.submitted_at', 'administrative_processes.updated_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function averageDays(array $filters, string $start, string $end): float
    {
        $query = $this->filters->applyApplication(
            Application::query()->join('administrative_processes', 'administrative_processes.application_id', '=', 'applications.id'),
            $filters,
            'applications.created_at',
        )->whereNotNull($start)->whereNotNull($end);

        if (DB::connection()->getDriverName() === 'sqlite') {
            $expression = $start === 'administrative_processes.received_at'
                ? 'AVG(julianday(administrative_processes.updated_at) - julianday(administrative_processes.received_at)) as average_days'
                : 'AVG(julianday(administrative_processes.updated_at) - julianday(applications.submitted_at)) as average_days';
        } else {
            $expression = $start === 'administrative_processes.received_at'
                ? 'AVG(TIMESTAMPDIFF(SECOND, administrative_processes.received_at, administrative_processes.updated_at)) / 86400 as average_days'
                : 'AVG(TIMESTAMPDIFF(SECOND, applications.submitted_at, administrative_processes.updated_at)) / 86400 as average_days';
        }

        return round((float) $query->selectRaw($expression)->value('average_days'), 2);
    }
}
