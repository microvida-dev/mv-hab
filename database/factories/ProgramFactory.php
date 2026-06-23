<?php

namespace Database\Factories;

use App\Enums\ProgramStatus;
use App\Models\Municipality;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Programa Municipal '.fake()->unique()->sentence(3);

        return [
            'municipality_id' => Municipality::factory(),
            'created_by' => null,
            'updated_by' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'summary' => fake()->sentence(16),
            'description' => fake()->paragraphs(3, true),
            'legal_basis' => 'Enquadramento legal de demonstração.',
            'status' => ProgramStatus::Draft->value,
            'starts_at' => today(),
            'ends_at' => today()->addYear(),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ProgramStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);
    }
}
