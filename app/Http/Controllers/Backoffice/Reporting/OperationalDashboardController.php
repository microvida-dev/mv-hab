<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\DashboardFilterRequest;
use App\Models\Contest;
use App\Models\Program;
use App\Services\Reporting\OperationalDashboardService;
use Illuminate\Contracts\View\View;

class OperationalDashboardController extends Controller
{
    public function __construct(private readonly OperationalDashboardService $dashboard) {}

    public function __invoke(DashboardFilterRequest $request): View
    {
        return view('backoffice.reports.dashboard-operational', $this->dashboard->build($this->authenticatedUser($request), $request->validated()) + [
            'filters' => $request->validated(),
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'contests' => Contest::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }
}
