<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Security\SensitiveDataAccessService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogSensitiveResourceAccess
{
    public function __construct(private readonly SensitiveDataAccessService $logs) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $user = $request->user();

        if ($user instanceof User && $response->getStatusCode() < 400) {
            foreach ($request->route()?->parameters() ?? [] as $parameter) {
                if ($parameter instanceof Model) {
                    $subject = null;
                    $subjectId = $parameter->getAttribute('user_id');

                    if (is_int($subjectId) || is_numeric($subjectId)) {
                        $foundSubject = User::query()->find((int) $subjectId);
                        $subject = $foundSubject instanceof User ? $foundSubject : null;
                    }

                    $this->logs->record($user, $parameter, 'view', $subject, 'personal', 'Acesso a rota sensível.');
                    break;
                }
            }
        }

        return $response;
    }
}
