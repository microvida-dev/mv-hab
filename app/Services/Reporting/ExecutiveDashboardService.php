<?php

namespace App\Services\Reporting;

use App\Models\User;
use App\Services\Reporting\Indicators\ApplicationIndicatorsService;

class ExecutiveDashboardService
{
    public function __construct(
        private readonly DashboardService $dashboards,
        private readonly ReportQueryService $queries,
        private readonly ApplicationIndicatorsService $applications,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $filters): array
    {
        return $this->dashboards->render('executive', $user, $filters) + [
            'by_status' => $this->applications->countApplicationsByStatus($filters),
            'by_contest' => $this->queries->applicationsByContest($filters),
        ];
    }
}
