<?php

namespace App\Http\Middleware;

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

        abort_unless($user instanceof User, 403);
        abort_if($permissions === [], 403);

        $authorized = collect($permissions)
            ->contains(
                fn (string $permission): bool => $user->hasPermission($permission)
            );

        abort_unless($authorized, 403);

        return $next($request);
    }
}
