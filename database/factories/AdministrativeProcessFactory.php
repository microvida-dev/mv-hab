<?php

namespace Database\Factories;

use App\Enums\AdministrativeProcessStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdministrativeProcess>
 */
class AdministrativeProcessFactory extends Factory
{
    public function definition(): array
    {
        return [
            'process_number' => 'PROC-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'application_id' => Application::factory()->submitted(),
            'program_id' => null,
            'contest_id' => null,
            'user_id' => User::factory(),
            'status' => AdministrativeProcessStatus::Received->value,
            'received_at' => now(),
            'summary' => 'Processo administrativo fictício para testes.',
        ];
    }
}
