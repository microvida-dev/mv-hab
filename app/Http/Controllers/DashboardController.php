<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardAuthorizationService;
use App\Services\Dashboard\ProfileDashboardService;
use App\Services\Productivity\ProductivityDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardAuthorizationService $authorization,
        ProfileDashboardService $dashboards,
        ProductivityDashboardService $productivity,
    ): View|RedirectResponse {
        $user = $this->authenticatedUser($request);

        abort_unless($authorization->isActive($user), 403);

        if ($user->hasRole('candidate')) {
            return to_route('candidate.dashboard');
        }

        $dashboard = $dashboards->forUser($user);
        $productivityDashboard = $productivity->forUser($user);

        return view('dashboard', [
            'dashboard' => $dashboard,
            'productivity' => $productivityDashboard,
            'workspaces' => $dashboard['workspaces'],
            'favorites' => $dashboard['favorites'],
            'recentItems' => $dashboard['recent_items'],
            'quickActions' => $dashboard['quick_actions'],
            'searchGroups' => $dashboard['search_groups'],
        ]);
    }
}
