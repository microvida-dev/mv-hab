<?php

namespace Database\Factories;

use App\Enums\ScoringRuleSetStatus;
use App\Models\Program;
use App\Models\ScoringRuleSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoringRuleSet>
 */
class ScoringRuleSetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'contest_id' => null,
            'name' => 'Matriz de classificação fictícia',
            'description' => 'Configuração fictícia para testes automatizados.',
            'status' => ScoringRuleSetStatus::Draft->value,
            'is_default' => false,
            'starts_at' => now()->subDay(),
            'ends_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ScoringRuleSetStatus::Active->value,
        ]);
    }
}
