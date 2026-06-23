<?php

namespace Database\Factories;

use App\Enums\ReportAccessType;
use App\Models\ReportAccessLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportAccessLog>
 */
class ReportAccessLogFactory extends Factory
{
    protected $model = ReportAccessLog::class;

    public function definition(): array
    {
        return ['user_id' => User::factory(), 'access_type' => ReportAccessType::ViewReport, 'filters' => [], 'ip_address' => '127.0.0.1', 'user_agent' => 'Testing', 'accessed_at' => now()];
    }
}
