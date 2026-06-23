<?php

namespace Database\Factories;

use App\Models\EligibilityCheck;
use App\Models\EligibilitySnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EligibilitySnapshot>
 */
class EligibilitySnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'eligibility_check_id' => EligibilityCheck::factory(),
            'snapshot_type' => fake()->unique()->randomElement([
                'adhesion_registration',
                'household',
                'income_records',
                'documents',
                'calculated_values',
            ]),
            'data' => ['test' => true],
        ];
    }
}
