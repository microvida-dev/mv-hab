<?php

namespace Database\Factories;

use App\Enums\TechnicalHistoryEventType;
use App\Models\HousingUnit;
use App\Models\PropertyHistoryEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyHistoryEvent>
 */
class PropertyHistoryEventFactory extends Factory
{
    protected $model = PropertyHistoryEvent::class;

    public function definition(): array
    {
        return [
            'housing_unit_id' => HousingUnit::factory(),
            'event_type' => fake()->randomElement(TechnicalHistoryEventType::values()),
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'occurred_at' => now(),
            'visible_to_tenant' => false,
        ];
    }
}
