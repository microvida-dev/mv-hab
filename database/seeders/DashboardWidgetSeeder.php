<?php

namespace Database\Seeders;

use App\Enums\DashboardWidgetType;
use App\Models\DashboardDefinition;
use App\Models\DashboardWidget;
use App\Models\IndicatorDefinition;
use Illuminate\Database\Seeder;

class DashboardWidgetSeeder extends Seeder
{
    public function run(): void
    {
        $widgets = [
            'operational' => ['applications_submitted', 'documents_pending', 'complaints_pending', 'housing_available', 'allocations_pending_response', 'finance_overdue_amount', 'maintenance_pending', 'communications_failed'],
            'executive' => ['applications_submitted', 'applications_eligible', 'applications_excluded', 'applications_average_analysis_days', 'housing_available', 'housing_allocated', 'housing_occupancy_rate', 'finance_overdue_amount', 'maintenance_pending', 'maintenance_total_costs'],
        ];

        foreach ($widgets as $dashboardCode => $indicatorCodes) {
            $dashboard = DashboardDefinition::query()->where('code', $dashboardCode)->firstOrFail();
            foreach ($indicatorCodes as $order => $indicatorCode) {
                $indicator = IndicatorDefinition::query()->where('code', $indicatorCode)->firstOrFail();
                $widget = DashboardWidget::query()->firstOrNew(['dashboard_definition_id' => $dashboard->id, 'code' => $indicatorCode]);
                $widget->forceFill(['indicator_definition_id' => $indicator->id, 'title' => $indicator->name, 'widget_type' => DashboardWidgetType::MetricCard, 'sort_order' => $order, 'width' => 1, 'required_permission' => $indicator->required_permission, 'is_active' => true])->save();
            }
        }
    }
}
