<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardAuthorizationService;
use App\Services\Dashboard\MunicipalOperationsDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardAuthorizationService $authorization,
        MunicipalOperationsDashboardService $operationsDashboard,
    ): View|RedirectResponse {
        $user = $this->authenticatedUser($request);

        abort_unless($authorization->isActive($user), 403);

        if ($user->hasRole('candidate')) {
            return to_route('candidate.dashboard');
        }

        return view('dashboard', $operationsDashboard->forUser($user));
    }
}
