<?php

namespace Database\Factories;

use App\Enums\DocumentAppliesTo;
use App\Enums\RequiredDocumentConditionOperator;
use App\Models\DocumentType;
use App\Models\RequiredDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequiredDocument>
 */
class RequiredDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_type_id' => DocumentType::factory(),
            'program_id' => null,
            'contest_id' => null,
            'required_for' => DocumentAppliesTo::AdhesionRegistration->value,
            'condition_key' => 'always',
            'condition_operator' => RequiredDocumentConditionOperator::Always->value,
            'condition_value' => null,
            'is_required' => true,
            'is_active' => true,
            'required_submissions' => 1,
            'reference_period_unit' => null,
            'requires_distinct_reference_periods' => false,
            'reference_period_recency' => null,
            'instructions' => 'Regra documental fictícia para testes.',
            'sort_order' => 0,
        ];
    }
}
