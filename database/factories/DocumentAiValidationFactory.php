<?php

namespace Database\Factories;

use App\Enums\DocumentAiComparisonMethod;
use App\Enums\DocumentAiValidationGroup;
use App\Enums\DocumentAiValidationSeverity;
use App\Enums\DocumentAiValidationStatus;
use App\Models\Application;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiValidation;
use App\Models\DocumentAiValidationRun;
use App\Models\DocumentSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAiValidation>
 */
class DocumentAiValidationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_ai_validation_run_id' => DocumentAiValidationRun::factory(),
            'document_ai_analysis_id' => DocumentAiAnalysis::factory(),
            'application_id' => Application::factory(),
            'document_submission_id' => DocumentSubmission::factory(),
            'validation_group' => DocumentAiValidationGroup::Identification->value,
            'validation_key' => 'name',
            'label' => 'Nome coincide',
            'status' => DocumentAiValidationStatus::Match->value,
            'severity' => DocumentAiValidationSeverity::None->value,
            'confidence' => '0.95',
            'candidate_value' => null,
            'extracted_value' => null,
            'candidate_value_hash' => null,
            'extracted_value_hash' => null,
            'value_type' => 'string',
            'comparison_method' => DocumentAiComparisonMethod::FuzzyName->value,
            'message' => 'Nome compatível com os dados declarados.',
            'recommendation' => null,
            'requires_manual_review' => false,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'review_notes' => null,
            'metadata' => ['sensitive' => true, 'health_data' => false],
        ];
    }
}
