<?php

namespace Database\Factories;

use App\Models\MfaDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MfaDevice> */
class MfaDeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'totp',
            'name' => 'Aplicação autenticadora',
            'secret_encrypted' => 'JBSWY3DPEHPK3PXP',
            'confirmed_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['confirmed_at' => now()]);
    }
}
