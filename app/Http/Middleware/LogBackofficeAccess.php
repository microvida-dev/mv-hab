<?php

namespace App\Http\Middleware;

use App\Enums\AccessLogType;
use App\Models\User;
use App\Services\Security\AccessLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogBackofficeAccess
{
    public function __construct(private readonly AccessLogService $logs) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $user = $request->user();

        if ($user instanceof User) {
            $this->logs->record(AccessLogType::AdminAccess, $user, statusCode: $response->getStatusCode());
        }

        return $response;
    }
}
