<?php

namespace Database\Factories;

use App\Enums\ConsentStatus;
use App\Models\ConsentPurpose;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserConsent> */
class UserConsentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'consent_purpose_id' => ConsentPurpose::factory(),
            'status' => ConsentStatus::Active->value,
            'consented_at' => now(),
            'source' => 'test',
            'text_snapshot' => 'Texto de consentimento demo.',
            'version' => 1,
        ];
    }
}
