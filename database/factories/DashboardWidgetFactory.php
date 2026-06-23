<?php

namespace Database\Factories;

use App\Enums\DashboardWidgetType;
use App\Models\DashboardDefinition;
use App\Models\DashboardWidget;
use App\Models\IndicatorDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DashboardWidget>
 */
class DashboardWidgetFactory extends Factory
{
    protected $model = DashboardWidget::class;

    public function definition(): array
    {
        return ['dashboard_definition_id' => DashboardDefinition::factory(), 'indicator_definition_id' => IndicatorDefinition::factory(), 'code' => fake()->unique()->slug(2), 'title' => fake()->sentence(2), 'widget_type' => DashboardWidgetType::MetricCard, 'sort_order' => 0, 'width' => 1, 'is_active' => true];
    }
}
