<?php

namespace Database\Factories;

use App\Models\BackofficeDashboardSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BackofficeDashboardSnapshot> */
class BackofficeDashboardSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'snapshot_number' => 'DASH-TEST-'.fake()->unique()->numerify('######'),
            'metrics' => ['applications' => ['total' => 0]],
            'generated_by' => User::factory(),
            'generated_at' => now(),
        ];
    }
}
