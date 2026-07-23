<?php

namespace App\Http\Middleware;

use App\Enums\FeatureKey;
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
            abort(403, 'Esta funcionalidade não está disponível para o Município atual.');
        }

        $user->loadMissing('municipality');

        if ($user->municipality === null || ! $this->entitlements->enabledFor($user->municipality, $feature)) {
            abort(403, 'Esta funcionalidade não está disponível para o Município atual.');
        }

        return $next($request);
    }
}
