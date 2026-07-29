<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\CandidateExperience\TenantSupportEligibilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSupportIsAvailable
{
    public function __construct(
        private readonly TenantSupportEligibilityService $eligibility,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User
                && $this->eligibility->isAvailableFor($user),
            403,
        );

        return $next($request);
    }
}
