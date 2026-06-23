<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Security\MfaEnforcementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBackofficeMfaVerified
{
    public function __construct(private readonly MfaEnforcementService $mfa) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = (string) $request->route()?->getName();

        if (str_starts_with($routeName, 'backoffice.security.mfa.')) {
            return $next($request);
        }

        if ($user instanceof User && $this->mfa->requiresMfa($user) && ! $this->mfa->sessionVerified()) {
            return redirect()->route('backoffice.security.mfa.index')
                ->with('warning', 'Configure ou confirme MFA para aceder a rotas sensíveis.');
        }

        return $next($request);
    }
}
