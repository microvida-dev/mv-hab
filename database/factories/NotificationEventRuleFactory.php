<?php

namespace Database\Factories;

use App\Enums\CommunicationChannel;
use App\Enums\NotificationPriority;
use App\Models\NotificationEventRule;
use App\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotificationEventRule> */
class NotificationEventRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_code' => 'test.event.'.fake()->unique()->numerify('#####'),
            'name' => 'Regra fictícia',
            'description' => 'Regra de teste.',
            'is_active' => true,
            'recipient_type' => 'custom_user',
            'channel' => CommunicationChannel::InApp,
            'notification_template_id' => NotificationTemplate::factory(),
            'requires_acknowledgement' => false,
            'priority' => NotificationPriority::Normal,
            'send_immediately' => true,
            'delay_minutes' => 0,
        ];
    }
}
