<?php

namespace App\Http\Middleware;

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

        abort_unless($user instanceof User && $user->hasRole($roleNames), 403);

        return $next($request);
    }
}
