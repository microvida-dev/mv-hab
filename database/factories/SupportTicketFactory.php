<?php

namespace Database\Factories;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_number' => 'SUP-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'user_id' => User::factory(),
            'category' => TicketCategory::Application->value,
            'priority' => TicketPriority::Normal->value,
            'status' => TicketStatus::Open->value,
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'context' => ['source' => 'factory'],
            'last_message_at' => now(),
        ];
    }
}
