<?php

namespace App\Services\Access;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\AccessChangeEvent;
use App\Models\MunicipalTeam;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AccessChangeLogger
{
    public function __construct(private readonly AuditTrailService $audit) {}

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

        $event = AccessChangeEvent::query()->create([
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
                'role_name' => $role?->name,
                'team_id' => $team?->id,
            ],
            subject: $target,
            related: $related,
            actor: $actor,
        );

        return $event;
    }

    private function auditable(?User $target, ?MunicipalTeam $team): ?Model
    {
        if ($target instanceof User) {
            return $target;
        }

        return $team;
    }

    private function request(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        return app(Request::class);
    }
}
