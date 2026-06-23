<?php

namespace Database\Factories;

use App\Enums\CommunicationChannel;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotificationPreference> */
class NotificationPreferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'allow_in_app' => true,
            'allow_email' => true,
            'allow_sms' => false,
            'allow_postal' => true,
            'preferred_channel' => CommunicationChannel::InApp,
            'email_for_notifications' => 'candidate@example.test',
            'consented_at' => now(),
        ];
    }
}
