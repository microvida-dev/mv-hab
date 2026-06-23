<?php

namespace Database\Factories;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AuditEvent> */
class AuditEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_number' => 'AUD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
            'user_id' => User::factory(),
            'event_code' => 'demo.event',
            'event_category' => AuditEventCategory::System->value,
            'severity' => AuditEventSeverity::Info->value,
            'description' => 'Evento de auditoria demo.',
            'occurred_at' => now(),
        ];
    }
}
