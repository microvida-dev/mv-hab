<?php

namespace Database\Factories;

use App\Enums\MaintenanceAssignmentStatus;
use App\Enums\MaintenanceAssignmentType;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceAssignment>
 */
class MaintenanceAssignmentFactory extends Factory
{
    protected $model = MaintenanceAssignment::class;

    public function definition(): array
    {
        return [
            'maintenance_request_id' => MaintenanceRequest::factory(),
            'assignment_type' => MaintenanceAssignmentType::InternalTechnician,
            'status' => MaintenanceAssignmentStatus::Assigned,
            'assigned_user_id' => User::factory(),
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
        ];
    }
}
