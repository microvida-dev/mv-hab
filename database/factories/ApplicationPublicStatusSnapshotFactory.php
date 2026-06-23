<?php

namespace Database\Factories;

use App\Enums\PublicProcessStatus;
use App\Models\Application;
use App\Models\ApplicationPublicStatusSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ApplicationPublicStatusSnapshot> */
class ApplicationPublicStatusSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'public_status' => PublicProcessStatus::Submitted->value,
            'internal_status' => 'submitted',
            'title' => 'Candidatura submetida',
            'description' => 'Estado público fictício para testes.',
            'next_step' => 'Aguardar análise.',
            'action_required' => false,
            'progress_percentage' => 25,
            'is_terminal' => false,
        ];
    }
}
