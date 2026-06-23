<?php

namespace Database\Factories;

use App\Enums\ExportScope;
use App\Enums\ReportFormat;
use App\Enums\ReportSensitivityLevel;
use App\Enums\ReportType;
use App\Models\ReportDefinition;
use App\Services\Reporting\ReportQueryService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportDefinition>
 */
class ReportDefinitionFactory extends Factory
{
    protected $model = ReportDefinition::class;

    public function definition(): array
    {
        return ['code' => fake()->unique()->slug(3), 'name' => fake()->sentence(3), 'report_type' => ReportType::Operational, 'sensitivity_level' => ReportSensitivityLevel::Restricted, 'query_service' => ReportQueryService::class, 'query_method' => 'applicationStatusSummary', 'available_formats' => [ReportFormat::Html->value, ReportFormat::Csv->value], 'available_scopes' => [ExportScope::Aggregated->value], 'requires_filters' => false, 'is_active' => true];
    }
}
