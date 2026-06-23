<?php

namespace Database\Factories;

use App\Enums\OfficialNotificationChannel;
use App\Enums\OfficialNotificationStatus;
use App\Enums\OfficialNotificationType;
use App\Models\OfficialNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OfficialNotification> */
class OfficialNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'notification_number' => 'NOT-TEST-'.fake()->unique()->numerify('########'),
            'user_id' => User::factory(),
            'notification_type' => OfficialNotificationType::Other->value,
            'status' => OfficialNotificationStatus::Queued->value,
            'channel' => OfficialNotificationChannel::CandidateArea->value,
            'subject' => 'Notificação fictícia',
            'body' => 'Conteúdo fictício.',
        ];
    }
}
