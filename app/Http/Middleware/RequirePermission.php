<?php

namespace App\Http\Middleware;

use App\Enums\AccessDenialReason;
use App\Exceptions\AccessDeniedException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequirePermission
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$permissions,
    ): Response {
        $user = $request->user();

        if (! $user instanceof User || $permissions === []) {
            throw new AccessDeniedException(AccessDenialReason::MissingPermission);
        }

        $authorized = collect($permissions)
            ->contains(
                fn (string $permission): bool => $user->hasPermission($permission)
            );

        if (! $authorized) {
            throw new AccessDeniedException(AccessDenialReason::MissingPermission);
        }

        return $next($request);
    }
}
