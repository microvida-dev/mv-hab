<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Enums\AccessDenialReason;
use App\Enums\ActorProfile;
use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Exceptions\AccessDeniedException;
use App\Http\Middleware\RequestCorrelationId;
use App\Models\Municipality;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PlatformMunicipalContextService
{
    public const SESSION_KEY = 'mvhab.platform.municipality_context_id';

    public function __construct(
        private readonly ActorProfileResolver $profiles,
        private readonly AuditTrailService $audit,
        private readonly Session $session,
        private readonly Request $request,
    ) {}

    public function hasPlatformScope(User $user): bool
    {
        return $this->profiles->primary($user)
            === ActorProfile::PlatformAdministrator;
    }

    public function currentMunicipality(User $user): ?Municipality
    {
        $storedValue = $this->session->get(self::SESSION_KEY);

        if ($storedValue === null) {
            return null;
        }

        $municipalityId = $this->positiveInteger($storedValue);

        if ($municipalityId === null) {
            $this->invalidate(
                $user,
                null,
                'invalid_session_identifier',
            );

            return null;
        }

        if (! $this->hasPlatformScope($user)) {
            $this->invalidate(
                $user,
                $municipalityId,
                'platform_assignment_unavailable',
            );

            return null;
        }

        $municipality = Municipality::query()
            ->whereKey($municipalityId)
            ->where('active', true)
            ->first();

        if (! $municipality instanceof Municipality) {
            $this->invalidate(
                $user,
                $municipalityId,
                'municipality_unavailable',
            );

            return null;
        }

        return $municipality;
    }

    public function effectiveMunicipality(User $user): ?Municipality
    {
        $profile = $this->profiles->primary($user);

        if ($profile === ActorProfile::PlatformAdministrator) {
            return $this->currentMunicipality($user);
        }

        if (! $profile->isMunicipalBackoffice()
            || $user->municipality_id === null) {
            return null;
        }

        return Municipality::query()
            ->whereKey((int) $user->municipality_id)
            ->where('active', true)
            ->first();
    }

    public function requireMunicipality(User $user): Municipality
    {
        $municipality = $this->effectiveMunicipality($user);

        if (! $municipality instanceof Municipality) {
            throw new AccessDeniedException(
                AccessDenialReason::RecordOutOfScope,
                ['operational_municipality_context' => false],
            );
        }

        return $municipality;
    }

    public function activate(
        User $user,
        Municipality $municipality,
        string $justification,
    ): void {
        $this->assertPlatformScope($user);
        $justification = $this->validatedJustification($justification);

        $activeMunicipality = Municipality::query()
            ->whereKey($municipality->getKey())
            ->where('active', true)
            ->first();

        if (! $activeMunicipality instanceof Municipality) {
            throw ValidationException::withMessages([
                'municipality_id' => 'O Município indicado não está disponível para operação.',
            ]);
        }

        $previousId = $this->positiveInteger(
            $this->session->get(self::SESSION_KEY),
        );
        $nextId = (int) $activeMunicipality->getKey();

        if ($previousId === $nextId) {
            return;
        }

        $eventCode = $previousId === null
            ? 'platform_municipal_context_entered'
            : 'platform_municipal_context_changed';

        $this->audit->record(
            $eventCode,
            $activeMunicipality,
            AuditEventCategory::System,
            AuditEventSeverity::Info,
            $previousId === null
                ? 'Operador global entrou num contexto municipal.'
                : 'Operador global alterou o contexto municipal.',
            oldValues: [
                'municipality_id' => $previousId,
            ],
            newValues: [
                'municipality_id' => $nextId,
            ],
            metadata: $this->metadata(
                $nextId,
                $previousId,
                $justification,
            ),
            actor: $user,
            useAuthenticatedUser: false,
        );

        $this->session->put(self::SESSION_KEY, $nextId);
        $this->regenerateSessionId();
    }

    public function clear(User $user, string $justification): void
    {
        $this->assertPlatformScope($user);
        $justification = $this->validatedJustification($justification);
        $previousId = $this->positiveInteger(
            $this->session->get(self::SESSION_KEY),
        );

        if ($previousId === null) {
            $this->session->forget(self::SESSION_KEY);

            return;
        }

        $previousMunicipality = Municipality::query()->find($previousId);

        $this->audit->record(
            'platform_municipal_context_cleared',
            $previousMunicipality,
            AuditEventCategory::System,
            AuditEventSeverity::Info,
            'Operador global saiu do contexto municipal.',
            oldValues: [
                'municipality_id' => $previousId,
            ],
            newValues: [
                'municipality_id' => null,
            ],
            metadata: $this->metadata(
                null,
                $previousId,
                $justification,
            ),
            actor: $user,
            useAuthenticatedUser: false,
        );

        $this->session->forget(self::SESSION_KEY);
        $this->regenerateSessionId();
    }

    public function isUsingMunicipalContext(User $user): bool
    {
        return $this->hasPlatformScope($user)
            && $this->currentMunicipality($user) instanceof Municipality;
    }

    private function assertPlatformScope(User $user): void
    {
        if (! $this->hasPlatformScope($user)) {
            throw new AccessDeniedException(
                AccessDenialReason::MissingPermission,
                ['platform_scope' => false],
            );
        }
    }

    private function validatedJustification(string $justification): string
    {
        $justification = trim($justification);

        if (mb_strlen($justification) < 10) {
            throw ValidationException::withMessages([
                'justification' => 'A justificação deve conter pelo menos 10 caracteres.',
            ]);
        }

        if (mb_strlen($justification) > 500) {
            throw ValidationException::withMessages([
                'justification' => 'A justificação não pode exceder 500 caracteres.',
            ]);
        }

        if ($justification !== strip_tags($justification)) {
            throw ValidationException::withMessages([
                'justification' => 'A justificação não pode conter HTML.',
            ]);
        }

        return $justification;
    }

    private function invalidate(
        User $user,
        ?int $previousMunicipalityId,
        string $reason,
    ): void {
        $municipality = $previousMunicipalityId === null
            ? null
            : Municipality::query()->find($previousMunicipalityId);

        $this->audit->record(
            'platform_municipal_context_invalidated',
            $municipality,
            AuditEventCategory::System,
            AuditEventSeverity::Warning,
            'O contexto municipal do operador global foi invalidado.',
            oldValues: [
                'municipality_id' => $previousMunicipalityId,
            ],
            newValues: [
                'municipality_id' => null,
            ],
            metadata: [
                'actor_id' => (int) $user->getKey(),
                'actor_scope' => 'platform',
                'municipality_id' => $previousMunicipalityId,
                'reason' => $reason,
                'request_id' => $this->requestId(),
            ],
            actor: $user,
            useAuthenticatedUser: false,
        );

        $this->session->forget(self::SESSION_KEY);
        $this->regenerateSessionId();
    }

    /**
     * @return array<string, int|string|null>
     */
    private function metadata(
        ?int $municipalityId,
        ?int $previousMunicipalityId,
        string $justification,
    ): array {
        return [
            'actor_scope' => 'platform',
            'municipality_id' => $municipalityId,
            'previous_municipality_id' => $previousMunicipalityId,
            'justification' => $justification,
            'request_id' => $this->requestId(),
        ];
    }

    private function requestId(): ?string
    {
        $requestId = $this->request->attributes->get(
            RequestCorrelationId::ATTRIBUTE,
        );

        return is_string($requestId) && $requestId !== ''
            ? $requestId
            : null;
    }

    private function positiveInteger(mixed $value): ?int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value)
            && ctype_digit($value)
            && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }

    private function regenerateSessionId(): void
    {
        if ($this->session->isStarted()) {
            $this->session->migrate(true);
        }
    }
}
