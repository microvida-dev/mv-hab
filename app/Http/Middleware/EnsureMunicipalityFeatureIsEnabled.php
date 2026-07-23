<?php

namespace App\Http\Middleware;

use App\Enums\AccessDenialReason;
use App\Enums\FeatureKey;
use App\Exceptions\AccessDeniedException;
use App\Models\User;
use App\Services\Entitlements\MunicipalityEntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMunicipalityFeatureIsEnabled
{
    public function __construct(private readonly MunicipalityEntitlementService $entitlements) {}

    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $feature = FeatureKey::tryFrom($featureKey);

        if ($feature === null) {
            abort(404);
        }

        $user = $request->user();

        if (! $user instanceof User || $user->municipality_id === null) {
            throw new AccessDeniedException(AccessDenialReason::FeatureUnavailable);
        }

        $user->loadMissing('municipality');

        if ($user->municipality === null || ! $this->entitlements->enabledFor($user->municipality, $feature)) {
            throw new AccessDeniedException(AccessDenialReason::FeatureUnavailable);
        }

        return $next($request);
    }
}
