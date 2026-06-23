<?php

namespace Database\Factories;

use App\Enums\ExportScope;
use App\Enums\ReportExportStatus;
use App\Enums\ReportFormat;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ReportExport>
 */
class ReportExportFactory extends Factory
{
    protected $model = ReportExport::class;

    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return ['public_id' => $uuid, 'report_run_id' => ReportRun::factory(), 'user_id' => User::factory(), 'status' => ReportExportStatus::Completed, 'requested_format' => ReportFormat::Csv, 'format' => ReportFormat::Csv, 'scope' => ExportScope::Aggregated, 'disk' => 'local', 'file_path' => "reports/tests/$uuid/report.csv", 'file_name' => 'report.csv', 'file_size' => 0, 'completed_at' => now(), 'expires_at' => now()->addDay()];
    }
}
