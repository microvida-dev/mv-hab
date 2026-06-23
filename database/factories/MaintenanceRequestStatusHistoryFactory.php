<?php

namespace Database\Factories;

use App\Enums\MaintenanceRequestStatus;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceRequestStatusHistory>
 */
class MaintenanceRequestStatusHistoryFactory extends Factory
{
    protected $model = MaintenanceRequestStatusHistory::class;

    public function definition(): array
    {
        return [
            'maintenance_request_id' => MaintenanceRequest::factory(),
            'from_status' => null,
            'to_status' => fake()->randomElement(MaintenanceRequestStatus::values()),
            'changed_by' => User::factory(),
            'changed_at' => now(),
        ];
    }
}
