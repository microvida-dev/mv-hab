<?php

namespace Database\Factories;

use App\Enums\RegistrationRenewalStatus;
use App\Models\AdhesionRegistration;
use App\Models\RegistrationRenewal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RegistrationRenewal>
 */
class RegistrationRenewalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'adhesion_registration_id' => AdhesionRegistration::factory(),
            'renewal_number' => 'REN-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'status' => RegistrationRenewalStatus::InProgress->value,
            'reason' => 'candidate_update',
            'previous_snapshot' => [],
            'updated_snapshot' => [],
            'changed_fields' => [],
            'missing_fields' => [],
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
        ];
    }
}
