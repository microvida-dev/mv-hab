<?php

namespace App\Http\Middleware;

use App\Enums\AccessDenialReason;
use App\Exceptions\AccessDeniedException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $roleNames = array_values($roles);

        if (! $user instanceof User || ! $user->hasRole($roleNames)) {
            $reason = $user instanceof User
                && $user->hasRole('candidate')
                && $this->isMunicipalOperation($request)
                    ? AccessDenialReason::CandidateBackofficeBoundary
                    : AccessDenialReason::MissingPermission;

            throw new AccessDeniedException($reason);
        }

        return $next($request);
    }

    private function isMunicipalOperation(Request $request): bool
    {
        $routeName = (string) $request->route()?->getName();

        return $request->is('backoffice/*', 'admin/*')
            || str_starts_with($routeName, 'backoffice.')
            || str_starts_with($routeName, 'admin.');
    }
}
