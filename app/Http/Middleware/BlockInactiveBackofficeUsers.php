<?php

namespace App\Http\Middleware;

use App\Enums\AccessDenialReason;
use App\Exceptions\AccessDeniedException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockInactiveBackofficeUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->hasRole('candidate')) {
            throw new AccessDeniedException(AccessDenialReason::CandidateBackofficeBoundary);
        }

        if ($user instanceof User && ($user->status ?? 'active') !== 'active') {
            throw new AccessDeniedException(AccessDenialReason::InactiveAccount);
        }

        return $next($request);
    }
}
