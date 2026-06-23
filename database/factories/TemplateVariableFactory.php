<?php

namespace Database\Factories;

use App\Enums\TemplateVariableType;
use App\Models\TemplateVariable;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TemplateVariable> */
class TemplateVariableFactory extends Factory
{
    public function definition(): array
    {
        $code = 'variable_'.fake()->unique()->numerify('#####');

        return [
            'code' => $code,
            'name' => 'Variável fictícia',
            'description' => 'Variável exclusiva de teste.',
            'variable_type' => TemplateVariableType::String,
            'source_key' => $code,
            'example_value' => 'Valor fictício',
            'is_required' => false,
            'is_sensitive' => false,
            'is_active' => true,
        ];
    }
}
