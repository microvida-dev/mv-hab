<?php

namespace Database\Factories;

use App\Enums\ContestStatus;
use App\Models\Contest;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Contest>
 */
class ContestFactory extends Factory
{
    public function definition(): array
    {
        $title = 'Concurso '.fake()->unique()->sentence(3);

        return [
            'program_id' => Program::factory(),
            'created_by' => null,
            'updated_by' => null,
            'code' => fake()->unique()->bothify('CAA-####'),
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'title' => $title,
            'summary' => fake()->sentence(16),
            'description' => fake()->paragraphs(3, true),
            'application_instructions' => 'Consulte os prazos e prepare a informação necessária.',
            'status' => ContestStatus::Draft->value,
            'opens_at' => now()->addWeek(),
            'closes_at' => now()->addMonth(),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ContestStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);
    }

    public function open(): static
    {
        return $this->published()->state(fn () => [
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addMonth(),
        ]);
    }
}
