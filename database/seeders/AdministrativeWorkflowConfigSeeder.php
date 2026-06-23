<?php

namespace Database\Seeders;

use App\Models\AdministrativeWorkflowConfig;
use App\Models\Program;
use Illuminate\Database\Seeder;

class AdministrativeWorkflowConfigSeeder extends Seeder
{
    public function run(): void
    {
        Program::query()
            ->limit(5)
            ->get()
            ->each(function (Program $program): void {
                AdministrativeWorkflowConfig::query()->updateOrCreate(
                    ['program_id' => $program->id, 'contest_id' => null, 'name' => 'Configuração base de aperfeiçoamento'],
                    [
                        'is_active' => true,
                        'default_correction_deadline_days' => 10,
                        'allow_deadline_extension' => false,
                        'max_deadline_extensions' => 0,
                        'auto_mark_overdue' => false,
                        'requires_decision_approval' => false,
                    ],
                );
            });
    }
}
