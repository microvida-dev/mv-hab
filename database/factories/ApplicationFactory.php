<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\Contest;
use App\Models\CurrentHousingSituation;
use App\Models\Household;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'application_number' => null,
            'user_id' => User::factory(),
            'adhesion_registration_id' => AdhesionRegistration::factory(),
            'program_id' => Program::factory(),
            'contest_id' => Contest::factory(),
            'household_id' => Household::factory(),
            'current_housing_situation_id' => CurrentHousingSituation::factory(),
            'status' => ApplicationStatus::Draft->value,
            'candidate_notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'application_number' => 'CAND-'.now()->format('Y').'-TEST-'.fake()->unique()->numerify('######'),
            'status' => ApplicationStatus::Submitted->value,
            'submitted_at' => now(),
            'locked_at' => now(),
            'declaration_accepted' => true,
            'declaration_accepted_at' => now(),
            'contest_rules_accepted' => true,
            'contest_rules_accepted_at' => now(),
            'data_processing_accepted' => true,
            'data_processing_accepted_at' => now(),
            'truthfulness_accepted' => true,
            'truthfulness_accepted_at' => now(),
            'data_current_confirmed' => true,
            'data_current_confirmed_at' => now(),
        ]);
    }
}
