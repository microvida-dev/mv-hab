<?php

namespace Database\Factories;

use App\Enums\IndicatorCategory;
use App\Enums\IndicatorValueType;
use App\Models\IndicatorDefinition;
use App\Services\Reporting\Indicators\ApplicationIndicatorsService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndicatorDefinition>
 */
class IndicatorDefinitionFactory extends Factory
{
    protected $model = IndicatorDefinition::class;

    public function definition(): array
    {
        return ['code' => fake()->unique()->slug(3), 'name' => fake()->sentence(3), 'category' => IndicatorCategory::Applications, 'value_type' => IndicatorValueType::Count, 'calculation_service' => ApplicationIndicatorsService::class, 'calculation_method' => 'countSubmittedApplications', 'is_active' => true];
    }
}
