<?php

namespace Database\Factories;

use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\IncomeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncomeRecord>
 */
class IncomeRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'household_member_id' => HouseholdMember::factory(),
            'household_id' => fn (array $attributes) => HouseholdMember::query()
                ->findOrFail($attributes['household_member_id'])
                ->household_id,
            'adhesion_registration_id' => fn (array $attributes) => Household::query()
                ->findOrFail($attributes['household_id'])
                ->adhesion_registration_id,
            'income_source_id' => IncomeSource::factory(),
            'description' => 'Rendimento fictício de teste',
            'monthly_amount' => 1000,
            'annual_amount' => 12000,
            'reference_year' => now()->year,
            'is_current' => true,
            'is_taxable' => true,
        ];
    }
}
