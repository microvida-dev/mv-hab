<?php

namespace Database\Factories;

use App\Enums\ProcedureTemplateStatus;
use App\Enums\ProcedureTemplateType;
use App\Models\ProcedureTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProcedureTemplate> */
class ProcedureTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'template_number' => 'MIN-TEST-'.fake()->unique()->numerify('######'),
            'type' => ProcedureTemplateType::ProcedureMinute,
            'status' => ProcedureTemplateStatus::Draft,
            'name' => 'Minuta de procedimento fictícia',
            'description' => 'Minuta fictícia para testes automatizados.',
            'content' => 'Procedimento {{process_number}} gerado em {{generated_at}}.',
            'variables' => ['process_number', 'generated_at'],
            'created_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ProcedureTemplateStatus::Active,
            'published_at' => now(),
            'published_by' => User::factory(),
        ]);
    }
}
