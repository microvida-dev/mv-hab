<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\AnalyticsFilterRequest;
use App\Models\Contest;
use App\Models\Program;
use App\Services\Analytics\DashboardAnalyticsService;
use Illuminate\Contracts\View\View;

class AnalyticsController extends Controller
{
    public function __construct(private readonly DashboardAnalyticsService $analytics) {}

    public function __invoke(AnalyticsFilterRequest $request): View
    {
        $filters = $request->validated();

        return view('backoffice.reports.analytics', [
            'analytics' => $this->analytics->forUser($this->authenticatedUser($request), $filters),
            'filters' => $filters,
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'contests' => Contest::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }
}
