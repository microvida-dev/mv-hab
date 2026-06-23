<?php

namespace Tests\Support;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentAiClassificationStatus;
use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiExtractedFieldType;
use App\Enums\DocumentAiExtractionSource;
use App\Enums\DocumentAiExtractionStatus;
use App\Enums\DocumentAiOcrStatus;
use App\Enums\DocumentAiStatus;
use App\Enums\DocumentAiValidationGroup;
use App\Enums\DocumentAiValidationSeverity;
use App\Enums\DocumentAiValidationStatus;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiField;
use App\Models\DocumentAiValidation;
use App\Models\DocumentAiValidationRun;
use App\Models\DocumentSubmission;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\User;

trait CreatesDocumentAiAssistantFixtures
{
    /**
     * @param  array<string, mixed>  $analysisOverrides
     * @return array{0: Application, 1: DocumentSubmission, 2: DocumentAiAnalysis}
     */
    protected function createAssistantAnalysis(array $analysisOverrides = []): array
    {
        $user = User::factory()->create(['name' => 'Candidato Ficticio']);
        $registration = AdhesionRegistration::factory()->registered()->create([
            'user_id' => $user->id,
            'full_name' => 'Candidato Ficticio',
            'nif' => '123456789',
            'birth_date' => '1988-05-12',
            'document_number' => 'ABC123456',
        ]);
        $household = Household::factory()->candidate($registration)->create([
            'members_count' => 1,
        ]);
        $member = HouseholdMember::factory()->applicant()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'full_name' => 'Candidato Ficticio',
            'nif' => '123456789',
            'birth_date' => '1988-05-12',
        ]);
        IncomeRecord::factory()->create([
            'household_member_id' => $member->id,
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'monthly_amount' => 1000,
            'annual_amount' => 12000,
        ]);
        $housing = CurrentHousingSituation::factory()->create([
            'adhesion_registration_id' => $registration->id,
            'current_address' => 'Rua de Teste 10',
            'current_city' => 'Alcanena',
            'current_municipality' => 'Alcanena',
            'current_monthly_rent' => 450,
        ]);
        $application = Application::factory()->submitted()->create([
            'user_id' => $user->id,
            'adhesion_registration_id' => $registration->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $housing->id,
            'status' => ApplicationStatus::Submitted->value,
        ]);
        $submission = DocumentSubmission::factory()->create([
            'user_id' => $user->id,
            'adhesion_registration_id' => $registration->id,
            'household_id' => $household->id,
            'application_id' => $application->id,
            'title' => 'Documento ficticio para assistente IA',
        ]);
        $analysis = DocumentAiAnalysis::factory()->completed()->create([
            'document_submission_id' => $submission->id,
            'status' => DocumentAiStatus::Completed,
            'source_sha256' => hash('sha256', 'documento-ficticio-'.$submission->id),
            'ocr_status' => DocumentAiOcrStatus::Completed,
            'ocr_available' => true,
            'ocr_text' => 'Declaracao fiscal ficticia com texto suficiente para validar OCR automatico e extracao estruturada.',
            'ocr_quality_score' => '0.94',
            'classification_status' => DocumentAiClassificationStatus::Completed,
            'detected_document_type' => DocumentAiDocumentType::Irs,
            'detected_document_label' => DocumentAiDocumentType::Irs->label(),
            'classification_confidence' => '0.95',
            'classification_requires_manual_review' => false,
            'extraction_status' => DocumentAiExtractionStatus::Completed,
            'extraction_confidence' => '0.91',
            'extraction_requires_manual_review' => false,
            ...$analysisOverrides,
        ]);

        $this->field($analysis, 'taxpayer_name', 'Sujeito passivo', 'Candidato Ficticio', DocumentAiExtractedFieldType::String);
        $this->field($analysis, 'nif', 'NIF', '123456789', DocumentAiExtractedFieldType::Identifier);
        $this->field($analysis, 'gross_income', 'Rendimento global', '12000.00', DocumentAiExtractedFieldType::Money);

        return [$application, $submission, $analysis->fresh(['fields', 'documentSubmission']) ?? $analysis];
    }

    protected function addNifDivergence(Application $application, DocumentAiAnalysis $analysis): DocumentAiValidation
    {
        $run = DocumentAiValidationRun::factory()->create([
            'application_id' => $application->id,
            'status' => DocumentAiValidationStatus::ManualReview->value,
            'total_checks' => 1,
            'critical_count' => 1,
            'requires_manual_review' => true,
        ]);

        return DocumentAiValidation::factory()->create([
            'document_ai_validation_run_id' => $run->id,
            'document_ai_analysis_id' => $analysis->id,
            'application_id' => $application->id,
            'document_submission_id' => $analysis->document_submission_id,
            'validation_group' => DocumentAiValidationGroup::Identification->value,
            'validation_key' => 'nif',
            'label' => 'NIF coincide',
            'status' => DocumentAiValidationStatus::Mismatch->value,
            'severity' => DocumentAiValidationSeverity::Critical->value,
            'confidence' => '0.93',
            'candidate_value' => '123456789',
            'extracted_value' => '987654321',
            'requires_manual_review' => true,
            'metadata' => ['sensitive' => true, 'health_data' => false],
        ]);
    }

    private function field(DocumentAiAnalysis $analysis, string $key, string $label, string $value, DocumentAiExtractedFieldType $type): void
    {
        DocumentAiField::factory()->create([
            'document_ai_analysis_id' => $analysis->id,
            'document_type' => $analysis->detected_document_type?->value,
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'normalized_value' => $value,
            'value_type' => $type->value,
            'confidence' => '0.94',
            'source' => DocumentAiExtractionSource::Regex->value,
            'requires_review' => false,
            'metadata' => ['category' => 'structured_extraction', 'sensitive' => true, 'health_data' => false],
        ]);
    }
}
