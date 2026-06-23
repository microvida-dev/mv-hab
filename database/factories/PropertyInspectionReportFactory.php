<?php

namespace Database\Factories;

use App\Enums\InspectionReportStatus;
use App\Models\PropertyInspection;
use App\Models\PropertyInspectionReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyInspectionReport>
 */
class PropertyInspectionReportFactory extends Factory
{
    protected $model = PropertyInspectionReport::class;

    public function definition(): array
    {
        return [
            'property_inspection_id' => PropertyInspection::factory(),
            'report_number' => 'AUTO-TEST-'.fake()->unique()->numerify('#####'),
            'status' => InspectionReportStatus::Generated,
            'storage_disk' => 'local',
            'storage_path' => 'inspections/reports/demo.html',
            'mime_type' => 'text/html',
        ];
    }
}
