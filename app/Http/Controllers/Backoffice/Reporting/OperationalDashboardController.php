<?php

namespace App\Http\Controllers\Backoffice\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporting\DashboardFilterRequest;
use App\Models\Contest;
use App\Models\Program;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Reporting\OperationalDashboardService;
use Illuminate\Contracts\View\View;

class OperationalDashboardController extends Controller
{
    public function __construct(
        private readonly OperationalDashboardService $dashboard,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function __invoke(DashboardFilterRequest $request): View
    {
        $user = $this->authenticatedUser($request);

        return view('backoffice.reports.dashboard-operational', $this->dashboard->build($user, $request->validated()) + [
            'filters' => $request->validated(),
            'programs' => $this->municipalScope
                ->programs(Program::query(), $user)
                ->orderBy('name')
                ->get(['id', 'name']),
            'contests' => $this->municipalScope
                ->contests(Contest::query(), $user)
                ->orderBy('title')
                ->get(['id', 'title']),
        ]);
    }
}
