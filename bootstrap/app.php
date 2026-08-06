<?php

use App\Http\Middleware\BlockInactiveBackofficeUsers;
use App\Http\Middleware\EnsureBackofficeMfaVerified;
use App\Http\Middleware\EnsureCandidateExperienceFeatureIsEnabled;
use App\Http\Middleware\EnsureMunicipalityFeatureIsEnabled;
use App\Http\Middleware\EnsureTenantSupportIsAvailable;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\LogBackofficeAccess;
use App\Http\Middleware\LogSensitiveResourceAccess;
use App\Http\Middleware\RequestCorrelationId;
use App\Http\Middleware\RequireOperationalMunicipalityContext;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\RequirePlatformAdministrator;
use App\Http\Middleware\RequireSensitivePermission;
use App\Services\Security\AuthorizationFailureResponder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(RequestCorrelationId::class);

        $middleware->alias([
            'active.backoffice' => BlockInactiveBackofficeUsers::class,
            'candidate.feature' => EnsureCandidateExperienceFeatureIsEnabled::class,
            'mfa.backoffice' => EnsureBackofficeMfaVerified::class,
            'tenant.support' => EnsureTenantSupportIsAvailable::class,
            'municipality.feature' => EnsureMunicipalityFeatureIsEnabled::class,
            'municipality.context' => RequireOperationalMunicipalityContext::class,
            'platform.operator' => RequirePlatformAdministrator::class,
            'role' => EnsureUserHasRole::class,
            'permission' => RequirePermission::class,
            'log.backoffice' => LogBackofficeAccess::class,
            'log.sensitive' => LogSensitiveResourceAccess::class,
            'sensitive.permission' => RequireSensitivePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (
            HttpExceptionInterface $exception,
            Request $request,
        ): ?Response {
            if ($exception->getStatusCode() !== 403) {
                return null;
            }

            return app(AuthorizationFailureResponder::class)
                ->respond($request, $exception);
        });

        $exceptions->respond(function (
            Response $response,
            Throwable $exception,
            Request $request,
        ): Response {
            $requestId = $request->attributes->get(RequestCorrelationId::ATTRIBUTE);

            if (is_string($requestId) && $requestId !== '') {
                $response->headers->set(RequestCorrelationId::HEADER, $requestId);
            }

            return $response;
        });
    })->create();
