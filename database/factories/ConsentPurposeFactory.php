<?php

namespace Database\Factories;

use App\Enums\ConsentLegalBasis;
use App\Models\ConsentPurpose;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ConsentPurpose> */
class ConsentPurposeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'name' => 'Finalidade demo',
            'description' => 'Finalidade de tratamento fictícia para testes.',
            'legal_basis' => ConsentLegalBasis::Consent->value,
            'is_required' => false,
            'is_active' => true,
            'requires_explicit_consent' => true,
            'retention_period_months' => 24,
        ];
    }
}
