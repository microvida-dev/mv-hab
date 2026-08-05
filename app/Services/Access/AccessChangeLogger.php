<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\AccessChangeEvent;
use App\Models\MunicipalTeam;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use App\Services\Platform\PlatformMunicipalContextService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use LogicException;

class AccessChangeLogger
{
    public function __construct(
        private readonly AuditTrailService $audit,
        private readonly PlatformMunicipalContextService $municipalContext,
    ) {}

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function record(
        string $eventCode,
        User $actor,
        string $justification,
        ?User $target = null,
        ?Role $role = null,
        ?MunicipalTeam $team = null,
        array $oldValues = [],
        array $newValues = [],
    ): AccessChangeEvent {
        $request = $this->request();
        $municipalityId = $this->municipalityId($actor, $target, $role, $team);

        $event = AccessChangeEvent::query()->create([
            'municipality_id' => $municipalityId,
            'event_code' => $eventCode,
            'actor_id' => $actor->id,
            'target_user_id' => $target?->id,
            'role_id' => $role?->id,
            'municipal_team_id' => $team?->id,
            'justification' => $justification,
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'occurred_at' => now(),
        ]);

        $auditable = $this->auditable($target, $team);
        $related = $role instanceof Role ? $role : $team;
        $this->audit->record(
            $eventCode,
            $auditable,
            AuditEventCategory::Security,
            AuditEventSeverity::Notice,
            $justification,
            $oldValues,
            $newValues,
            [
                'access_change_event_id' => $event->id,
                'municipality_id' => $municipalityId,
                'role_name' => $role?->name,
                'team_id' => $team?->id,
            ],
            subject: $target,
            related: $related,
            actor: $actor,
            useAuthenticatedUser: false,
            municipalityId: $municipalityId,
        );

        return $event;
    }

    private function municipalityId(User $actor, ?User $target, ?Role $role, ?MunicipalTeam $team): int
    {
        $municipalityId = $target instanceof User
            ? $target->municipality_id
            : null;

        if ($municipalityId === null && $team instanceof MunicipalTeam) {
            $municipalityId = $team->municipality_id;
        }

        if ($municipalityId === null && $role instanceof Role) {
            $municipalityId = $role->municipality_id;
        }

        if ($municipalityId === null) {
            $municipalityId = $this->municipalContext
                ->effectiveMunicipality($actor)
                ?->getKey();
        }

        if ($municipalityId === null) {
            throw new LogicException('Um evento de acesso exige Município efetivo.');
        }

        return (int) $municipalityId;
    }

    private function auditable(?User $target, ?MunicipalTeam $team): ?Model
    {
        return $target instanceof User ? $target : $team;
    }

    private function request(): ?Request
    {
        return app()->bound('request') ? app(Request::class) : null;
    }
}
