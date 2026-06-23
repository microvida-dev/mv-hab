<?php

namespace Database\Factories;

use App\Enums\DefinitiveListStatus;
use App\Models\DefinitiveList;
use App\Models\ProvisionalList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DefinitiveList> */
class DefinitiveListFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provisional_list_id' => ProvisionalList::factory(),
            'list_number' => 'LD-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'title' => 'Lista definitiva fictícia',
            'status' => DefinitiveListStatus::Draft->value,
            'version_number' => 1,
            'generated_by' => User::factory(),
            'generated_at' => now(),
            'anonymization_mode' => 'public_identifier_only',
            'public_visibility' => false,
        ];
    }
}
