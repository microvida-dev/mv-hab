<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\AccessDenialReason;
use App\Enums\ActorProfile;
use App\Exceptions\AccessDeniedException;
use App\Models\User;
use App\Services\Platform\ActorProfileResolver;
use App\Services\Platform\PlatformMunicipalContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequirePlatformAdministrator
{
    public function __construct(
        private readonly ActorProfileResolver $profiles,
        private readonly PlatformMunicipalContextService $contexts,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AccessDeniedException(
                AccessDenialReason::MissingPermission,
                ['platform_scope' => false],
            );
        }

        // Resolve the stored context first so revoked assignments, disabled
        // accounts and unavailable municipalities invalidate stale sessions.
        $this->contexts->currentMunicipality($user);

        if ($this->profiles->primary($user)
            !== ActorProfile::PlatformAdministrator) {
            throw new AccessDeniedException(
                AccessDenialReason::MissingPermission,
                ['platform_scope' => false],
            );
        }

        return $next($request);
    }
}
