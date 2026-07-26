<?php

namespace Database\Factories;

use App\Enums\SecurityAlertSeverity;
use App\Models\Municipality;
use App\Models\SecurityAlertRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SecurityAlertRule> */
class SecurityAlertRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'municipality_id' => fn (): int => (int) (
                Municipality::query()->value('id')
                ?? Municipality::factory()->create()->id
            ),
            'code' => fake()->unique()->slug(2),
            'name' => 'Regra de alerta demo',
            'event_code' => 'demo.event',
            'severity' => SecurityAlertSeverity::Medium->value,
            'threshold' => 1,
            'window_minutes' => 15,
            'is_active' => true,
        ];
    }
}
