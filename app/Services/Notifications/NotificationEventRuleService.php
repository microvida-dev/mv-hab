<?php

namespace App\Services\Notifications;

use App\Models\NotificationEventRule;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class NotificationEventRuleService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, User $actor): NotificationEventRule
    {
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
        $rule->fill($data);
        $rule->forceFill(['updated_by' => $actor->id])->save();
        $this->audit->record(AuditEvents::UPDATE, $rule, 'notifications', 'notification_event_rule_updated', 'Regra de comunicação atualizada.');

        return $rule->refresh();
    }

    public function setActive(NotificationEventRule $rule, bool $active, User $actor): NotificationEventRule
    {
        $rule->forceFill(['is_active' => $active, 'updated_by' => $actor->id])->save();

        return $rule->refresh();
    }
}
