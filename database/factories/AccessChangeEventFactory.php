<?php

namespace Database\Factories;

use App\Models\AccessChangeEvent;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AccessChangeEvent> */
class AccessChangeEventFactory extends Factory
{
    protected $model = AccessChangeEvent::class;

    public function definition(): array
    {
        return [
            'event_code' => 'user_updated',
            'actor_id' => User::factory(),
            'target_user_id' => User::factory(),
            'justification' => fake()->sentence(),
            'old_values' => [],
            'new_values' => [],
            'occurred_at' => now(),
        ];
    }

    public function forMunicipality(Municipality|int $municipality): static
    {
        $municipalityId = $municipality instanceof Municipality
            ? $municipality->getKey()
            : $municipality;

        return $this->state(fn (): array => ['municipality_id' => $municipalityId]);
    }
}
