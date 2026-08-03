<?php

namespace App\Services\Notifications;

use App\Models\Contest;
use App\Models\NotificationEventRule;
use App\Models\NotificationTemplate;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\CommunicationMunicipalContextService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;

class NotificationEventRuleService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly CommunicationMunicipalContextService $context,
        private readonly ProceduralNotificationRuleGuard $proceduralGuard,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, User $actor): NotificationEventRule
    {
        abort_unless(
            $actor->hasPermission('notification_event_rules.create')
                && $this->municipalScope->hasMunicipalOrGlobalScope($actor),
            403,
        );
        $data = $this->normalizeContext($data, $actor);

        $rule = new NotificationEventRule($data);
        $rule->forceFill(['created_by' => $actor->id, 'updated_by' => $actor->id])->save();
        $this->audit->record(AuditEvents::CREATE, $rule, 'notifications', 'notification_event_rule_created', 'Regra de comunicação criada.');

        return $rule;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(NotificationEventRule $rule, array $data, User $actor): NotificationEventRule
    {
        abort_unless(
            $actor->hasPermission('notification_event_rules.update')
                && $this->municipalScope->canMutateNotificationEventRule(
                    $actor,
                    $rule,
                ),
            403,
        );
        $data = $this->normalizeContext($data, $actor, $rule);
        $this->proceduralGuard->assertUpdateAllowed($rule, $data);

        $rule->fill($data);
        $rule->forceFill(['updated_by' => $actor->id])->save();
        $this->audit->record(AuditEvents::UPDATE, $rule, 'notifications', 'notification_event_rule_updated', 'Regra de comunicação atualizada.');

        return $rule->refresh();
    }

    public function setActive(NotificationEventRule $rule, bool $active, User $actor): NotificationEventRule
    {
        $permission = $active
            ? 'notification_event_rules.activate'
            : 'notification_event_rules.deactivate';
        abort_unless(
            $actor->hasPermission($permission)
                && $this->municipalScope->canMutateNotificationEventRule(
                    $actor,
                    $rule,
                ),
            403,
        );

        if (! $active) {
            $this->proceduralGuard->assertCanDeactivate($rule);
        }

        $rule->forceFill(['is_active' => $active, 'updated_by' => $actor->id])->save();
        $this->audit->record(
            AuditEvents::UPDATE,
            $rule,
            'notifications',
            $active
                ? 'notification_event_rule_activated'
                : 'notification_event_rule_deactivated',
            $active
                ? 'Regra de comunicação ativada.'
                : 'Regra de comunicação desativada.',
        );

        return $rule->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeContext(
        array $data,
        User $actor,
        ?NotificationEventRule $rule = null,
    ): array {
        $templateId = $this->contextId(
            $data,
            'notification_template_id',
            $rule?->notification_template_id,
        );
        abort_unless($templateId !== null, 422);
        $template = $this->municipalScope
            ->notificationTemplates(
                NotificationTemplate::query(),
                $actor,
            )
            ->whereKey($templateId)
            ->firstOrFail();

        $programId = $this->contextId(
            $data,
            'program_id',
            $rule?->program_id,
        );
        $contestId = $this->contextId(
            $data,
            'contest_id',
            $rule?->contest_id,
        );
        $municipalityIds = [];
        $templateMunicipalityId = $this->context->forModel($template);

        if ($templateMunicipalityId !== null) {
            $municipalityIds[] = $templateMunicipalityId;
        }

        if ($programId !== null) {
            $program = $this->municipalScope
                ->programs(Program::query(), $actor)
                ->whereKey($programId)
                ->firstOrFail();
            $municipalityIds[] = (int) $program->municipality_id;
        }

        if ($contestId !== null) {
            $contest = $this->municipalScope
                ->contests(Contest::query(), $actor)
                ->whereKey($contestId)
                ->with('program:id,municipality_id')
                ->firstOrFail();
            abort_if(
                $programId !== null
                    && (int) $contest->program_id !== $programId,
                422,
            );
            $municipalityIds[] = (int) $contest->program?->municipality_id;
        }

        if ($actor->municipality_id !== null) {
            $municipalityIds[] = (int) $actor->municipality_id;
        } elseif (
            $municipalityIds === []
            && $rule?->municipality_id !== null
        ) {
            $municipalityIds[] = (int) $rule->municipality_id;
        }

        $data['municipality_id'] = $this->singleMunicipality(
            $municipalityIds,
        );

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function contextId(
        array $data,
        string $key,
        mixed $fallback,
    ): ?int {
        $value = array_key_exists($key, $data)
            ? $data[$key]
            : $fallback;

        return is_numeric($value) && (int) $value > 0
            ? (int) $value
            : null;
    }

    /**
     * @param  list<int>  $municipalityIds
     */
    private function singleMunicipality(array $municipalityIds): ?int
    {
        $municipalityIds = array_values(array_unique(array_filter(
            $municipalityIds,
            static fn (int $id): bool => $id > 0,
        )));

        abort_if(count($municipalityIds) > 1, 422);

        return $municipalityIds[0] ?? null;
    }
}
