<?php

namespace Database\Factories;

use App\Enums\AllocationMethod;
use App\Enums\AllocationStatus;
use App\Models\Allocation;
use App\Models\AllocationRuleSet;
use App\Models\AllocationRun;
use App\Models\Application;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use App\Models\HousingUnit;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Allocation> */
class AllocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'allocation_run_id' => AllocationRun::factory(),
            'allocation_rule_set_id' => AllocationRuleSet::factory(),
            'program_id' => Program::factory(),
            'contest_id' => Contest::factory(),
            'definitive_list_id' => DefinitiveList::factory(),
            'definitive_list_entry_id' => DefinitiveListEntry::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'contest_housing_unit_id' => ContestHousingUnit::factory(),
            'housing_unit_id' => HousingUnit::factory(),
            'allocation_method' => AllocationMethod::Ranking->value,
            'status' => AllocationStatus::Proposed->value,
            'rank_position' => 1,
            'allocated_by' => User::factory(),
            'allocated_at' => now(),
            'acceptance_deadline_at' => now()->addDays(10),
        ];
    }
}
