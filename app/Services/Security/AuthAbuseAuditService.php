<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Enums\Security\HumanVerificationContext;
use App\Enums\Security\HumanVerificationFailureReason;
use App\Http\Middleware\RequestCorrelationId;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

final class AuthAbuseAuditService
{
    private const DEDUPLICATION_SECONDS = 60;

    public function __construct(
        private readonly AuditTrailService $audit,
    ) {}

    public function recordHumanVerificationFailure(
        Request $request,
        HumanVerificationContext $context,
        HumanVerificationFailureReason $reason,
    ): void {
        $this->record(
            request: $request,
            eventCode: 'human_verification_failed',
            description: 'Falha na verificação humana de um fluxo de autenticação.',
            metadata: [
                'context' => $context->value,
                'reason_category' => $reason->value,
            ],
        );
    }

    public function recordRateLimitExceeded(
        Request $request,
        string $operation,
    ): void {
        $this->record(
            request: $request,
            eventCode: 'auth_rate_limit_exceeded',
            description: 'Limite técnico de um fluxo de autenticação excedido.',
            metadata: [
                'operation' => $operation,
            ],
        );
    }

    /**
     * @param  array<string, scalar|null>  $metadata
     */
    private function record(
        Request $request,
        string $eventCode,
        string $description,
        array $metadata,
    ): void {
        $emailHash = $this->emailHash($request);
        $ipHash = $this->ipHash($request);
        $requestId = $request->attributes->get(RequestCorrelationId::ATTRIBUTE);
        $routeName = $request->route()?->getName();
        $deduplicationFingerprint = implode('|', [
            $eventCode,
            (string) $routeName,
            (string) $emailHash,
            (string) $ipHash,
            (string) json_encode($metadata),
        ]);

        try {
            if (! Cache::add(
                'auth-abuse-audit:'.hash('sha256', $deduplicationFingerprint),
                true,
                now()->addSeconds(self::DEDUPLICATION_SECONDS),
            )) {
                return;
            }
        } catch (Throwable) {
            // A indisponibilidade da cache não pode alterar uma recusa segura.
        }

        $user = $request->user();
        $actor = $user instanceof User ? $user : null;

        try {
            $this->audit->record(
                eventCode: $eventCode,
                category: AuditEventCategory::Security,
                severity: AuditEventSeverity::Warning,
                description: $description,
                metadata: array_filter([
                    ...$metadata,
                    'route_name' => is_string($routeName) ? $routeName : null,
                    'email_hash' => $emailHash,
                    'ip_hash' => $ipHash,
                    'request_id' => is_string($requestId) ? $requestId : null,
                    'timestamp' => now()->toIso8601String(),
                ], static fn (mixed $value): bool => $value !== null),
                subject: $actor,
                actor: $actor,
                useAuthenticatedUser: false,
            );
        } catch (Throwable) {
            // A auditoria é best effort e nunca deve converter uma recusa em erro 500.
        }
    }

    private function emailHash(Request $request): ?string
    {
        $email = $request->input('email');

        if (! is_string($email) || trim($email) === '') {
            return null;
        }

        return hash('sha256', Str::lower(trim($email)));
    }

    private function ipHash(Request $request): ?string
    {
        $ipAddress = $request->ip();

        return is_string($ipAddress) && $ipAddress !== ''
            ? hash('sha256', $ipAddress)
            : null;
    }
}
