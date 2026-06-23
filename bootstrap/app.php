<?php

use App\Http\Middleware\BlockInactiveBackofficeUsers;
use App\Http\Middleware\EnforcePasswordPolicyOnChange;
use App\Http\Middleware\EnsureBackofficeMfaVerified;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\LogBackofficeAccess;
use App\Http\Middleware\LogSensitiveResourceAccess;
use App\Http\Middleware\RequireSensitivePermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active.backoffice' => BlockInactiveBackofficeUsers::class,
            'mfa.backoffice' => EnsureBackofficeMfaVerified::class,
            'password.policy' => EnforcePasswordPolicyOnChange::class,
            'role' => EnsureUserHasRole::class,
            'log.backoffice' => LogBackofficeAccess::class,
            'log.sensitive' => LogSensitiveResourceAccess::class,
            'sensitive.permission' => RequireSensitivePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
