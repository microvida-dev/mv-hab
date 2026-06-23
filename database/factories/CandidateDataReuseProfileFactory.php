<?php

namespace Database\Factories;

use App\Enums\CandidateDataReuseProfileStatus;
use App\Models\CandidateDataReuseProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CandidateDataReuseProfile>
 */
class CandidateDataReuseProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'profile_number' => 'RDP-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'status' => CandidateDataReuseProfileStatus::Active->value,
            'registration_snapshot' => [],
            'household_snapshot' => [],
            'income_snapshot' => [],
            'housing_snapshot' => [],
            'documents_snapshot' => [],
            'source_payload' => [],
            'last_confirmed_at' => now(),
            'expires_at' => now()->addMonths(6),
        ];
    }
}
