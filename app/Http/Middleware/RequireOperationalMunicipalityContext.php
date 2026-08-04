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

final class RequireOperationalMunicipalityContext
{
    public const ATTRIBUTE = 'operational_municipality';

    public const ATTRIBUTE_ID = 'operational_municipality_id';

    public function __construct(
        private readonly ActorProfileResolver $profiles,
        private readonly PlatformMunicipalContextService $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AccessDeniedException(
                AccessDenialReason::MissingPermission,
            );
        }

        // Resolve first to invalidate a stale session context when the
        // structural platform assignment was revoked between requests.
        $this->context->currentMunicipality($user);

        $profile = $this->profiles->primary($user);

        if ($profile === ActorProfile::Candidate) {
            throw new AccessDeniedException(
                AccessDenialReason::CandidateBackofficeBoundary,
            );
        }

        if ($profile !== ActorProfile::PlatformAdministrator
            && ! $profile->isMunicipalBackoffice()) {
            throw new AccessDeniedException(
                AccessDenialReason::MissingPermission,
            );
        }

        $municipality = $this->context->requireMunicipality($user);

        $request->attributes->set(self::ATTRIBUTE, $municipality);
        $request->attributes->set(
            self::ATTRIBUTE_ID,
            (int) $municipality->getKey(),
        );

        return $next($request);
    }
}
