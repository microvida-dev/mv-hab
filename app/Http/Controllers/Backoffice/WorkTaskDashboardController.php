<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Policies\WorkTaskDashboardPolicy;
use App\Services\Workflows\WorkTaskDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WorkTaskDashboardController extends Controller
{
    public function __construct(
        private readonly WorkTaskDashboardService $dashboardService,
        private readonly WorkTaskDashboardPolicy $policy,
    ) {}

    public function __invoke(Request $request): View
    {
        abort_unless($this->policy->view($this->authenticatedUser($request)), 403);

        return view('backoffice.work-tasks.dashboard', [
            'metrics' => $this->dashboardService->metrics($this->authenticatedUser($request)),
        ]);
    }
}
