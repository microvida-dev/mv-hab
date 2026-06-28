<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\DashboardFilterRequest;
use App\Models\Contest;
use App\Models\Program;
use App\Services\Analytics\ExecutiveDashboardService as AnalyticsExecutiveDashboardService;
use App\Services\Reporting\ExecutiveDashboardService;
use Illuminate\Contracts\View\View;

class ExecutiveDashboardController extends Controller
{
    public function __construct(
        private readonly ExecutiveDashboardService $dashboard,
        private readonly AnalyticsExecutiveDashboardService $analytics,
    ) {}

    public function __invoke(DashboardFilterRequest $request): View
    {
        $filters = $request->validated();
        $user = $this->authenticatedUser($request);

        return view('backoffice.reports.dashboard-executive', $this->dashboard->build($user, $filters) + [
            'analytics' => $this->analytics->build($user, $filters),
            'filters' => $filters,
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'contests' => Contest::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }
}
