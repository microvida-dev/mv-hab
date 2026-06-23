<?php

namespace Database\Factories;

use App\Enums\ListAutomationStatus;
use App\Enums\ListAutomationType;
use App\Models\Contest;
use App\Models\ListAutomationRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ListAutomationRun> */
class ListAutomationRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'run_number' => 'LIST-AUTO-TEST-'.fake()->unique()->numerify('######'),
            'contest_id' => Contest::factory(),
            'type' => ListAutomationType::Provisional,
            'status' => ListAutomationStatus::Generated,
            'total_candidates' => 0,
            'included_count' => 0,
            'excluded_count' => 0,
            'warnings_count' => 0,
            'criteria_snapshot' => ['source' => 'factory'],
            'result_payload' => ['entries' => []],
            'generated_by' => User::factory(),
            'generated_at' => now(),
        ];
    }
}
