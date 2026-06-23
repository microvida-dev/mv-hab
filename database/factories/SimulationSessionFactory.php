<?php

namespace Database\Factories;

use App\Enums\SimulationScope;
use App\Enums\SimulationSessionStatus;
use App\Models\SimulationSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SimulationSession>
 */
class SimulationSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => null,
            'scope' => SimulationScope::Anonymous->value,
            'status' => SimulationSessionStatus::Completed->value,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'expires_at' => now()->addDays(30),
            'source' => 'factory',
        ];
    }

    public function forCandidate(?User $user = null): static
    {
        return $this->state(fn () => [
            'user_id' => $user instanceof User ? $user->id : User::factory(),
            'scope' => SimulationScope::Authenticated->value,
        ]);
    }
}
