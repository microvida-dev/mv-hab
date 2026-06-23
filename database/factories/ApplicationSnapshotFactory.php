<?php

namespace Database\Factories;

use App\Enums\ApplicationSnapshotType;
use App\Models\Application;
use App\Models\ApplicationSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationSnapshot>
 */
class ApplicationSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'snapshot_type' => fake()->randomElement(ApplicationSnapshotType::cases())->value,
            'data' => ['fixture' => true],
        ];
    }
}
