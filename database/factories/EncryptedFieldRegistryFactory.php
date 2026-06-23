<?php

namespace Database\Factories;

use App\Enums\EncryptedFieldStatus;
use App\Models\EncryptedFieldRegistry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EncryptedFieldRegistry> */
class EncryptedFieldRegistryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'model_class' => User::class,
            'table_name' => 'users',
            'field_name' => fake()->unique()->word(),
            'encryption_status' => EncryptedFieldStatus::Planned->value,
            'search_strategy' => 'Estratégia demo.',
            'migration_required' => true,
        ];
    }
}
