<?php

namespace Database\Factories;

use App\Enums\ProcessConfirmationStatus;
use App\Models\Application;
use App\Models\ProcessConfirmation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProcessConfirmation> */
class ProcessConfirmationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'confirmation_number' => 'CONF-TEST-'.fake()->unique()->numerify('######'),
            'process_number' => 'PROC-TEST-'.fake()->unique()->numerify('######'),
            'application_id' => Application::factory(),
            'user_id' => fn (array $attributes) => Application::query()->find($attributes['application_id'])->user_id ?? User::factory(),
            'contest_id' => fn (array $attributes) => Application::query()->find($attributes['application_id'])?->contest_id,
            'status' => ProcessConfirmationStatus::Generated,
            'title' => 'Confirmação de receção fictícia',
            'message' => 'Confirmação fictícia para testes automatizados.',
            'payload' => ['source' => 'factory'],
            'generated_by' => User::factory(),
        ];
    }
}
