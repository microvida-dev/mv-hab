<?php

namespace Database\Factories;

use App\Enums\InteractionType;
use App\Models\CandidateInteraction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateInteraction>
 */
class CandidateInteractionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'interaction_type' => InteractionType::TicketCreated->value,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'metadata' => ['source' => 'factory'],
            'occurred_at' => now(),
        ];
    }
}
