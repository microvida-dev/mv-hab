<?php

namespace Database\Factories;

use App\Enums\AccessLogType;
use App\Models\AccessLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AccessLog> */
class AccessLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'access_type' => AccessLogType::PageView->value,
            'route_name' => 'demo.route',
            'request_path' => '/demo',
            'session_id_hash' => hash('sha256', fake()->uuid()),
            'status_code' => 200,
            'accessed_at' => now(),
        ];
    }
}
