<?php

namespace Database\Factories;

use App\Enums\SecurityAlertSeverity;
use App\Enums\SecurityAlertStatus;
use App\Models\SecurityAlert;
use App\Models\SecurityAlertRule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SecurityAlert> */
class SecurityAlertFactory extends Factory
{
    public function definition(): array
    {
        return [
            'alert_number' => 'SEC-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
            'security_alert_rule_id' => SecurityAlertRule::factory(),
            'status' => SecurityAlertStatus::Open->value,
            'severity' => SecurityAlertSeverity::Medium->value,
            'title' => 'Alerta demo',
            'detected_at' => now(),
        ];
    }
}
