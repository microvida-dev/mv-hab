<?php

namespace Database\Factories;

use App\Enums\ContestClosureStatus;
use App\Models\Contest;
use App\Models\ContestClosure;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContestClosure> */
class ContestClosureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'contest_id' => Contest::factory(),
            'closure_number' => 'FC-'.fake()->unique()->numerify('######'),
            'status' => ContestClosureStatus::Closed->value,
            'summary' => [],
            'critical_pending_items' => [],
            'snapshot' => [],
            'closed_at' => now(),
        ];
    }
}
