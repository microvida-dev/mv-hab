<?php

namespace Database\Factories;

use App\Enums\AllocationMethod;
use App\Enums\AllocationRunStatus;
use App\Models\AllocationRuleSet;
use App\Models\AllocationRun;
use App\Models\Contest;
use App\Models\DefinitiveList;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AllocationRun> */
class AllocationRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'allocation_rule_set_id' => AllocationRuleSet::factory(),
            'program_id' => Program::factory(),
            'contest_id' => Contest::factory(),
            'definitive_list_id' => DefinitiveList::factory(),
            'run_number' => 'ATR-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'status' => AllocationRunStatus::Completed->value,
            'allocation_method' => AllocationMethod::Ranking->value,
            'started_by' => User::factory(),
            'started_at' => now(),
            'completed_at' => now(),
        ];
    }
}
