<?php

namespace Database\Factories;

use App\Enums\ExportScope;
use App\Enums\ReportFormat;
use App\Enums\ReportRunStatus;
use App\Models\ReportDefinition;
use App\Models\ReportRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ReportRun>
 */
class ReportRunFactory extends Factory
{
    protected $model = ReportRun::class;

    public function definition(): array
    {
        return ['public_id' => (string) Str::uuid(), 'report_definition_id' => ReportDefinition::factory(), 'user_id' => User::factory(), 'status' => ReportRunStatus::Completed, 'format' => ReportFormat::Html, 'scope' => ExportScope::Aggregated, 'filters' => [], 'row_count' => 0, 'started_at' => now(), 'completed_at' => now()];
    }
}
