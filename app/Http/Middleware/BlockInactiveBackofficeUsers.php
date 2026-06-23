<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockInactiveBackofficeUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user instanceof User && ! $user->hasRole('candidate') && ($user->status ?? 'active') !== 'active', 403);

        return $next($request);
    }
}
