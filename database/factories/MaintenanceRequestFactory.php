<?php

namespace Database\Factories;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceRequestStatus;
use App\Enums\MaintenanceSource;
use App\Enums\MaintenanceUrgency;
use App\Models\Citizen;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceRequest>
 */
class MaintenanceRequestFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(MaintenanceRequestStatus::values());

        return [
            'housing_unit_id' => HousingUnit::factory(),
            'citizen_id' => fake()->boolean(70) ? Citizen::factory() : null,
            'request_number' => 'MAN-TEST-'.fake()->unique()->numerify('#####'),
            'source' => fake()->randomElement(MaintenanceSource::values()),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'urgency' => fake()->randomElement(MaintenanceUrgency::values()),
            'priority' => fake()->randomElement(MaintenancePriority::values()),
            'status' => $status,
            'reported_at' => fake()->dateTimeBetween('-2 months', 'now'),
            'resolved_at' => $status === MaintenanceRequestStatus::Resolved->value
                ? fake()->dateTimeBetween('-1 month', 'now')
                : null,
        ];
    }
}
