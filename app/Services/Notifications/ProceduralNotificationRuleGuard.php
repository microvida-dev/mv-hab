<?php

namespace App\Services\Notifications;

use App\Enums\CommunicationChannel;
use App\Models\NotificationEventRule;
use App\Models\NotificationTemplate;
use Illuminate\Validation\ValidationException;

final class ProceduralNotificationRuleGuard
{
    public function __construct(
        private readonly ProceduralNotificationPolicy $policy,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function assertUpdateAllowed(
        NotificationEventRule $rule,
        array $data,
    ): void {
        if (! $this->isMandatoryOfficialRule($rule)) {
            return;
        }

        $protected = [
            'event_code' => $rule->event_code,
            'channel' => $rule->channel->value,
            'notification_template_id' => $rule->notification_template_id,
            'is_active' => true,
        ];

        foreach ($protected as $key => $expected) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $actual = $data[$key];

            if ($actual instanceof CommunicationChannel) {
                $actual = $actual->value;
            }

            if ((string) $actual !== (string) $expected) {
                throw ValidationException::withMessages([
                    $key => 'Esta regra integra o circuito obrigatório de comunicações oficiais e não pode remover o canal ou alterar a sua identidade.',
                ]);
            }
        }
    }

    public function assertCanDeactivate(
        NotificationEventRule $rule,
    ): void {
        if (! $this->isMandatoryOfficialRule($rule)) {
            return;
        }

        throw ValidationException::withMessages([
            'is_active' => 'As regras obrigatórias de email e área pessoal não podem ser desativadas.',
        ]);
    }

    private function isMandatoryOfficialRule(
        NotificationEventRule $rule,
    ): bool {
        $rule->loadMissing('template');
        $template = $rule->template;

        return $template instanceof NotificationTemplate
            && in_array($rule->channel, [
                CommunicationChannel::Email,
                CommunicationChannel::InApp,
            ], true)
            && $this->policy->requiresMandatoryEmail(
                $rule->event_code,
                (bool) $template->is_official,
            );
    }
}
