<?php

namespace Database\Factories;

use App\Enums\InspectionType;
use App\Models\InspectionChecklistTemplate;
use App\Models\Municipality;
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
            'municipality_id' => Municipality::factory(),
            'is_system' => false,
            'code' => 'INSP-'.fake()->unique()->bothify('??##'),
            'name' => 'Checklist demo '.fake()->word(),
            'inspection_type' => fake()->randomElement(
                InspectionType::values(),
            ),
            'is_active' => true,
            'version_number' => 1,
        ];
    }

    public function system(): static
    {
        return $this->state(fn (): array => [
            'municipality_id' => null,
            'is_system' => true,
        ]);
    }
}
