<?php

namespace Database\Factories;

use App\Enums\InspectionCondition;
use App\Models\PropertyInspection;
use App\Models\PropertyInspectionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyInspectionItem>
 */
class PropertyInspectionItemFactory extends Factory
{
    protected $model = PropertyInspectionItem::class;

    public function definition(): array
    {
        return [
            'property_inspection_id' => PropertyInspection::factory(),
            'label' => fake()->randomElement(['Paredes', 'Pavimentos', 'Instalação elétrica']),
            'condition' => fake()->randomElement(InspectionCondition::values()),
            'requires_maintenance' => false,
        ];
    }
}
