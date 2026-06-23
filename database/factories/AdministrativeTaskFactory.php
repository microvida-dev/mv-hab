<?php

namespace Database\Factories;

use App\Enums\AdministrativeTaskPriority;
use App\Enums\AdministrativeTaskStatus;
use App\Models\AdministrativeProcess;
use App\Models\AdministrativeTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdministrativeTask>
 */
class AdministrativeTaskFactory extends Factory
{
    public function definition(): array
    {
        $process = AdministrativeProcess::factory()->create();

        return [
            'administrative_process_id' => $process->id,
            'application_id' => $process->application_id,
            'title' => 'Tarefa administrativa fictícia',
            'description' => 'Descrição fictícia para teste.',
            'status' => AdministrativeTaskStatus::Open->value,
            'priority' => AdministrativeTaskPriority::Normal->value,
            'assigned_to' => User::factory(),
            'due_at' => now()->addDays(3),
            'created_by' => User::factory(),
        ];
    }
}
