<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePasswordPolicyOnChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('put') || $request->isMethod('patch') || $request->isMethod('post')) {
            $password = (string) $request->input('password');
            abort_if($password !== '' && strlen($password) < 12, 422, 'A política de segurança recomenda password com pelo menos 12 caracteres.');
        }

        return $next($request);
    }
}
