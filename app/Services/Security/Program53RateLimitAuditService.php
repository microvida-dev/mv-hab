<?php

namespace App\Services\Security;

use App\Http\Middleware\RequestCorrelationId;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class Program53RateLimitAuditService
{
    private const DEDUPLICATION_SECONDS = 60;

    public function __construct(private readonly AuditLogger $audit) {}

    public function record(Request $request, string $operation, string $dimension): void
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return;
        }

        $routeName = (string) $request->route()?->getName();
        $requestId = $request->attributes->get(RequestCorrelationId::ATTRIBUTE);
        $fingerprint = implode('|', [
            (string) $user->id,
            (string) ($user->municipality_id ?? 0),
            $routeName,
            $operation,
            $dimension,
        ]);

        try {
            if (! Cache::add(
                'program53-rate-limit-audit:'.hash('sha256', $fingerprint),
                true,
                now()->addSeconds(self::DEDUPLICATION_SECONDS),
            )) {
                return;
            }
        } catch (Throwable) {
            // A indisponibilidade da cache não pode alterar a resposta 429.
        }

        try {
            $this->audit->record(
                event: 'program53_rate_limit_exceeded',
                auditable: $user,
                module: 'security',
                action: 'throttle',
                description: 'Limite técnico de uma operação do Programa 53 excedido.',
                metadata: [
                    'actor_id' => (int) $user->id,
                    'municipality_id' => $user->municipality_id !== null
                        ? (int) $user->municipality_id
                        : null,
                    'route_name' => $routeName,
                    'operation' => $operation,
                    'dimension' => $dimension,
                    'request_id' => is_string($requestId) ? $requestId : null,
                    'timestamp' => now()->toIso8601String(),
                ],
            );
        } catch (Throwable) {
            // A auditoria é best effort para não converter uma recusa segura em erro 500.
        }
    }
}
