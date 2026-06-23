<?php

namespace Database\Seeders;

use App\Enums\RentCalculationMethod;
use App\Enums\RentRuleSetStatus;
use App\Models\Program;
use App\Models\RentRuleSet;
use Illuminate\Database\Seeder;

class RentRuleSetSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::query()->first();

        if (! $program) {
            return;
        }

        RentRuleSet::query()->firstOrCreate(
            ['program_id' => $program->id, 'name' => 'Regra demo de renda - sujeita a validação jurídica'],
            [
                'status' => RentRuleSetStatus::Draft,
                'calculation_method' => RentCalculationMethod::EffortRate,
                'income_period' => 'monthly',
                'income_basis' => 'declared_income',
                'effort_rate_percentage' => 30,
                'minimum_rent' => 50,
                'maximum_rent' => 500,
                'deposit_months' => 1,
                'requires_manual_approval' => true,
                'allow_manual_override' => true,
            ],
        );
    }
}
