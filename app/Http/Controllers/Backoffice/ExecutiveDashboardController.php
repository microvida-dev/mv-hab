<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\FilterExecutiveDashboardRequest;
use App\Services\BackofficeDashboard\ExecutiveDashboardService;
use Illuminate\Contracts\View\View;

class ExecutiveDashboardController extends Controller
{
    public function __construct(private readonly ExecutiveDashboardService $dashboard) {}

    public function index(FilterExecutiveDashboardRequest $request): View
    {
        $user = $this->authenticatedUser($request);

        abort_if($user->hasRole('candidate'), 403);
        $metrics = $this->dashboard->build($request->validated(), $user);

        return view('backoffice.dashboard.executive', compact('metrics'));
    }
}
