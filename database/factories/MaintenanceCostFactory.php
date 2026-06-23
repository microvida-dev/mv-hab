<?php

namespace Database\Factories;

use App\Enums\MaintenanceCostStatus;
use App\Enums\MaintenanceCostType;
use App\Models\HousingUnit;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceCost>
 */
class MaintenanceCostFactory extends Factory
{
    protected $model = MaintenanceCost::class;

    public function definition(): array
    {
        return [
            'maintenance_request_id' => MaintenanceRequest::factory(),
            'housing_unit_id' => HousingUnit::factory(),
            'cost_type' => fake()->randomElement(MaintenanceCostType::values()),
            'status' => MaintenanceCostStatus::Estimated,
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 10, 750),
            'currency' => 'EUR',
        ];
    }
}
