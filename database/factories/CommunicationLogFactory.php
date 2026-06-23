<?php

namespace Database\Factories;

use App\Enums\CommunicationStatus;
use App\Enums\NotificationPriority;
use App\Models\CommunicationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunicationLog> */
class CommunicationLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'communication_number' => 'COM-TEST-'.fake()->unique()->numerify('########'),
            'event_code' => 'test.communication',
            'status' => CommunicationStatus::Queued,
            'priority' => NotificationPriority::Normal,
            'recipient_user_id' => User::factory(),
            'recipient_name' => 'Utilizador fictício',
            'recipient_email' => 'candidate@example.test',
            'subject' => 'Assunto fictício',
            'title' => 'Comunicação fictícia',
            'body_snapshot' => 'Conteúdo fictício sem dados reais.',
            'is_official' => false,
            'requires_acknowledgement' => false,
            'queued_at' => now(),
        ];
    }
}
