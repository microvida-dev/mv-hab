<?php

namespace Database\Factories;

use App\Enums\TenantPortalStatus;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TenantProfile> */
class TenantProfileFactory extends Factory
{
    protected $model = TenantProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => TenantPortalStatus::Active->value,
            'activated_at' => now(),
            'activation_notes' => 'Perfil de inquilino criado para validação funcional.',
        ];
    }
}
