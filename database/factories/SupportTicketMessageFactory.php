<?php

namespace Database\Factories;

use App\Enums\MessageVisibility;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicketMessage>
 */
class SupportTicketMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'support_ticket_id' => SupportTicket::factory(),
            'sender_user_id' => User::factory(),
            'visibility' => MessageVisibility::CandidateVisible->value,
            'message' => fake()->paragraph(),
        ];
    }
}
