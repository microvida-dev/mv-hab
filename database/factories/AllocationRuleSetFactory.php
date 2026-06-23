<?php

namespace Database\Factories;

use App\Enums\AllocationMethod;
use App\Enums\AllocationRuleSetStatus;
use App\Models\AllocationRuleSet;
use App\Models\Contest;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AllocationRuleSet> */
class AllocationRuleSetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'contest_id' => Contest::factory(),
            'name' => 'Regra de atribuição fictícia',
            'status' => AllocationRuleSetStatus::Active->value,
            'allocation_method' => AllocationMethod::Ranking->value,
            'allow_preferences' => true,
            'allow_lottery' => true,
            'allow_manual_override' => true,
            'requires_acceptance' => true,
            'acceptance_deadline_days' => 10,
            'auto_call_next_on_refusal' => true,
            'auto_call_next_on_expiry' => true,
            'max_refusals_allowed' => 1,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
