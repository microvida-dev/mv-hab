<?php

namespace Database\Factories;

use App\Models\SimulatorConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulatorConfiguration>
 */
class SimulatorConfigurationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Configuração geral do simulador',
            'is_active' => true,
            'anonymous_simulator_enabled' => true,
            'candidate_simulator_enabled' => true,
            'max_recommended_contests' => 5,
            'default_effort_rate' => 35,
            'session_retention_days' => 30,
            'settings' => [],
        ];
    }
}
