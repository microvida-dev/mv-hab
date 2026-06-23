<?php

namespace Database\Factories;

use App\Models\AdministrativeWorkflowConfig;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdministrativeWorkflowConfig>
 */
class AdministrativeWorkflowConfigFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'contest_id' => null,
            'name' => 'Configuração administrativa fictícia',
            'is_active' => true,
            'default_correction_deadline_days' => 10,
            'allow_deadline_extension' => false,
            'max_deadline_extensions' => 0,
            'auto_mark_overdue' => false,
            'requires_decision_approval' => false,
        ];
    }
}
