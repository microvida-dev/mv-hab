<?php

namespace Database\Factories;

use App\Enums\InspectionType;
use App\Models\InspectionChecklistTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InspectionChecklistTemplate>
 */
class InspectionChecklistTemplateFactory extends Factory
{
    protected $model = InspectionChecklistTemplate::class;

    public function definition(): array
    {
        return [
            'code' => 'INSP-'.fake()->unique()->bothify('??##'),
            'name' => 'Checklist demo '.fake()->word(),
            'inspection_type' => fake()->randomElement(InspectionType::values()),
            'is_active' => true,
            'version_number' => 1,
        ];
    }
}
