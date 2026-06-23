<?php

namespace Database\Seeders;

use App\Models\NotificationEventRule;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationEventRuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (NotificationTemplate::query()->where('is_default', true)->where('status', 'active')->get() as $template) {
            $eventCode = str($template->code)->beforeLast('_')->beforeLast('_')->toString();
            $recipientType = in_array($eventCode, ['maintenance_request_created'], true) ? 'maintenance_manager' : 'candidate';

            NotificationEventRule::query()->updateOrCreate(
                [
                    'event_code' => $eventCode,
                    'recipient_type' => $recipientType,
                    'channel' => $template->channel->value,
                ],
                [
                    'name' => 'Regra demo · '.$template->name,
                    'description' => 'Regra inicial sujeita a validação funcional e municipal.',
                    'is_active' => true,
                    'notification_template_id' => $template->id,
                    'requires_acknowledgement' => $template->requires_acknowledgement,
                    'priority' => in_array($eventCode, ['payment_overdue', 'housing_allocated'], true) ? 'high' : 'normal',
                    'send_immediately' => true,
                    'delay_minutes' => 0,
                ],
            );
        }
    }
}
