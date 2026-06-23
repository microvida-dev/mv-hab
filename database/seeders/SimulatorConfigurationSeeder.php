<?php

namespace Database\Seeders;

use App\Models\SimulatorConfiguration;
use Illuminate\Database\Seeder;

class SimulatorConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        SimulatorConfiguration::query()->updateOrCreate(
            ['name' => 'Configuração geral do simulador'],
            [
                'is_active' => true,
                'anonymous_simulator_enabled' => true,
                'candidate_simulator_enabled' => true,
                'max_recommended_contests' => 5,
                'default_effort_rate' => 35,
                'session_retention_days' => 30,
                'settings' => [
                    'legal_notice_required' => true,
                    'allow_application_prefill' => true,
                    'allow_registration_renewal' => true,
                ],
            ],
        );
    }
}
