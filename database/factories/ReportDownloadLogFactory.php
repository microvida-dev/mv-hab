<?php

namespace Database\Factories;

use App\Models\ReportDownloadLog;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportDownloadLog>
 */
class ReportDownloadLogFactory extends Factory
{
    protected $model = ReportDownloadLog::class;

    public function definition(): array
    {
        return ['report_export_id' => ReportExport::factory(), 'user_id' => User::factory(), 'ip_address' => '127.0.0.1', 'user_agent' => 'Testing', 'downloaded_at' => now()];
    }
}
