<?php

namespace Database\Factories;

use App\Enums\InspectionStatus;
use App\Enums\InspectionType;
use App\Models\HousingUnit;
use App\Models\PropertyInspection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyInspection>
 */
class PropertyInspectionFactory extends Factory
{
    protected $model = PropertyInspection::class;

    public function definition(): array
    {
        return [
            'inspection_number' => 'VIS-TEST-'.fake()->unique()->numerify('#####'),
            'housing_unit_id' => HousingUnit::factory(),
            'inspection_type' => fake()->randomElement(InspectionType::values()),
            'status' => InspectionStatus::Draft,
            'scheduled_for' => now()->addDays(5),
        ];
    }
}
