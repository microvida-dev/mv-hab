<?php

namespace App\Http\Middleware;

use App\Enums\AccessDenialReason;
use App\Exceptions\AccessDeniedException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSensitivePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->hasPermission($permission)) {
            throw new AccessDeniedException(AccessDenialReason::MissingPermission);
        }

        return $next($request);
    }
}
