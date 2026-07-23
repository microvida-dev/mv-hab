<?php

namespace App\Services\Security;

use App\Enums\AccessDenialReason;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class AuthorizationDenialAuditService
{
    private const DEDUPLICATION_SECONDS = 60;

    public function __construct(private readonly AuditLogger $audit) {}

    public function record(
        Request $request,
        AccessDenialReason $reason,
        string $requestId,
    ): void {
        $user = $request->user();

        if (
            ! $user instanceof User
            || (! $reason->shouldAudit() && $request->isMethodSafe())
        ) {
            return;
        }

        $deduplicationKey = $this->deduplicationKey($request, $user, $reason);

        try {
            if (! Cache::add(
                $deduplicationKey,
                true,
                now()->addSeconds(self::DEDUPLICATION_SECONDS),
            )) {
                return;
            }
        } catch (Throwable) {
            // A indisponibilidade da cache não pode ocultar uma recusa relevante.
        }

        $this->audit->record(
            event: 'authorization_denied',
            auditable: $user,
            module: 'security',
            action: 'deny',
            description: 'Recusa de autorização registada.',
            metadata: [
                'actor_id' => $user->id,
                'municipality_id' => $user->municipality_id,
                'route_name' => $request->route()?->getName(),
                'http_method' => $request->method(),
                'denial_reason' => $reason->value,
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ],
        );
    }

    private function deduplicationKey(
        Request $request,
        User $user,
        AccessDenialReason $reason,
    ): string {
        $fingerprint = implode('|', [
            (string) $user->id,
            (string) ($user->municipality_id ?? 0),
            (string) $request->route()?->getName(),
            $request->method(),
            $reason->value,
        ]);

        return 'authorization-denial:'.hash('sha256', $fingerprint);
    }
}
