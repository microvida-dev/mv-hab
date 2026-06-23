<?php

namespace Database\Factories;

use App\Enums\ListChangeType;
use App\Models\Application;
use App\Models\ListChangeLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ListChangeLog> */
class ListChangeLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'change_type' => ListChangeType::Other->value,
            'reason' => 'Alteração fictícia.',
            'created_at' => now(),
        ];
    }
}
