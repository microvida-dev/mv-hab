<?php

namespace App\Http\Controllers;

use App\Enums\AccessDenialReason;
use App\Enums\ActorProfile;
use App\Exceptions\AccessDeniedException;
use App\Services\Dashboard\DashboardAuthorizationService;
use App\Services\Dashboard\MunicipalOperationsDashboardService;
use App\Services\Dashboard\PlatformOperationsDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardAuthorizationService $authorization,
        MunicipalOperationsDashboardService $operationsDashboard,
        PlatformOperationsDashboardService $platformDashboard,
    ): View|RedirectResponse {
        $user = $this->authenticatedUser($request);

        abort_unless($authorization->isActive($user), 403);

        $profile = $authorization->actorProfile($user);

        if ($profile === ActorProfile::Candidate) {
            if (! $user->hasVerifiedEmail()) {
                return to_route('verification.notice');
            }

            return to_route('candidate.dashboard');
        }

        if ($profile === ActorProfile::PlatformAdministrator) {
            return view(
                'dashboard-platform',
                $platformDashboard->forUser($user),
            );
        }

        if (! $profile->isMunicipalBackoffice()) {
            throw new AccessDeniedException(
                AccessDenialReason::MissingPermission,
                ['actor_profile' => $profile->value],
            );
        }

        return view('dashboard', $operationsDashboard->forUser($user));
    }
}
