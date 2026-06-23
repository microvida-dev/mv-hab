<?php

namespace Database\Factories;

use App\Enums\ApplicationPrefillStatus;
use App\Models\ApplicationPrefill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationPrefill>
 */
class ApplicationPrefillFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => ApplicationPrefillStatus::PendingConfirmation->value,
            'prefill_payload' => [],
            'fields_included' => ['registration'],
            'fields_excluded' => ['documents'],
            'warnings' => [],
            'expires_at' => now()->addDays(30),
        ];
    }
}
