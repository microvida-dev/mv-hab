<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\FilterOperationalDashboardRequest;
use App\Services\BackofficeDashboard\OperationalDashboardService;
use Illuminate\Contracts\View\View;

class OperationalDashboardController extends Controller
{
    public function __construct(private readonly OperationalDashboardService $dashboard) {}

    public function index(FilterOperationalDashboardRequest $request): View
    {
        $user = $this->authenticatedUser($request);

        abort_if($user->hasRole('candidate'), 403);
        $dashboard = $this->dashboard->build($request->validated(), $user);

        return view('backoffice.dashboard.operational', compact('dashboard'));
    }
}
