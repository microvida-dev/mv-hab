<?php

namespace Database\Factories;

use App\Enums\EligibilityRuleSetStatus;
use App\Models\EligibilityRuleSet;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EligibilityRuleSet>
 */
class EligibilityRuleSetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'contest_id' => null,
            'name' => 'Regra de elegibilidade '.fake()->unique()->sentence(2),
            'description' => 'Conjunto fictício para testes funcionais.',
            'status' => EligibilityRuleSetStatus::Draft->value,
            'is_default' => false,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => EligibilityRuleSetStatus::Active->value,
        ]);
    }
}
