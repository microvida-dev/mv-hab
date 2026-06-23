<?php

namespace Database\Factories;

use App\Enums\MaintenanceInterventionStatus;
use App\Models\HousingUnit;
use App\Models\MaintenanceIntervention;
use App\Models\MaintenanceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceIntervention>
 */
class MaintenanceInterventionFactory extends Factory
{
    protected $model = MaintenanceIntervention::class;

    public function definition(): array
    {
        return [
            'maintenance_request_id' => MaintenanceRequest::factory(),
            'housing_unit_id' => HousingUnit::factory(),
            'status' => MaintenanceInterventionStatus::Planned,
            'scheduled_for' => now()->addDays(3),
        ];
    }
}
