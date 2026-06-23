<?php

namespace Database\Factories;

use App\Models\SensitiveDataAccessLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SensitiveDataAccessLog> */
class SensitiveDataAccessLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject_user_id' => User::factory(),
            'resource_type' => User::class,
            'resource_id' => 1,
            'sensitivity_level' => 'personal',
            'access_reason' => 'Acesso demo autorizado.',
            'action' => 'view',
            'accessed_at' => now(),
        ];
    }
}
