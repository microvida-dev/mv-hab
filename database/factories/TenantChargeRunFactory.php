<?php

namespace Database\Factories;

use App\Enums\ChargeRunStatus;
use App\Enums\ChargeType;
use App\Models\TenantChargeRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TenantChargeRun> */
class TenantChargeRunFactory extends Factory
{
    protected $model = TenantChargeRun::class;

    public function definition(): array
    {
        return [
            'run_number' => 'TCR-'.fake()->unique()->numerify('######'),
            'status' => ChargeRunStatus::Draft->value,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'charge_type' => ChargeType::Rent->value,
        ];
    }
}
