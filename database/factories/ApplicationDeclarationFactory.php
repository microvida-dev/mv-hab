<?php

namespace Database\Factories;

use App\Enums\ApplicationDeclarationType;
use App\Models\Application;
use App\Models\ApplicationDeclaration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationDeclaration>
 */
class ApplicationDeclarationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'declaration_type' => fake()->randomElement(ApplicationDeclarationType::cases())->value,
            'accepted' => true,
            'accepted_at' => now(),
            'text_version' => 'test.v1',
        ];
    }
}
