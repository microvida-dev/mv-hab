<?php

namespace Tests\Feature\DocumentIntelligence;

use App\Enums\DocumentAiClassificationStatus;
use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiExtractedFieldType;
use App\Enums\DocumentAiExtractionSource;
use App\Enums\DocumentAiExtractionStatus;
use App\Enums\DocumentAiOcrStatus;
use App\Enums\DocumentAiStatus;
use App\Enums\DocumentAiValidationSeverity;
use App\Enums\DocumentAiValidationStatus;
use App\Events\DocumentCandidateCriticalDivergenceDetected;
use App\Events\DocumentCandidateValidationCompleted;
use App\Events\DocumentCandidateValidationRequiresReview;
use App\Events\DocumentCandidateValidationStarted;
use App\Jobs\ValidateDocumentAiAgainstApplicationJob;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiField;
use App\Models\DocumentSubmission;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\User;
use App\Services\DocumentIntelligence\DocumentCandidateValidationPipeline;
use App\Services\DocumentIntelligence\DocumentFieldExtractionPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DocumentCandidateValidationPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_pipeline_creates_validation_alerts_without_changing_application_status(): void
    {
        Event::fake([
            DocumentCandidateValidationStarted::class,
            DocumentCandidateValidationCompleted::class,
            DocumentCandidateValidationRequiresReview::class,
            DocumentCandidateCriticalDivergenceDetected::class,
        ]);

        [$application, $analysis] = $this->applicationWithExtractedIrs(grossIncome: '18000.00');
        $originalStatus = $application->status;

        $run = app(DocumentCandidateValidationPipeline::class)->processAnalysis($analysis, $application);

        $this->assertSame(DocumentAiValidationStatus::ManualReview, $run->status, (string) $run->failure_reason);
        $this->assertTrue($run->requires_manual_review);
        $this->assertSame(1, $run->critical_count);
        $this->assertSame($originalStatus, $application->fresh()->status);
        $this->assertDatabaseHas('document_ai_validations', [
            'document_ai_validation_run_id' => $run->id,
            'application_id' => $application->id,
            'validation_key' => 'gross_income',
            'severity' => DocumentAiValidationSeverity::Critical->value,
            'requires_manual_review' => true,
        ]);
        $this->assertDatabaseHas('document_ai_flags', [
            'document_ai_analysis_id' => $analysis->id,
            'code' => 'candidate_validation_divergencia_critica_gross_income',
            'requires_manual_review' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $run->getMorphClass(),
            'auditable_id' => $run->id,
            'action' => 'document_ai_candidate_validation_completed',
        ]);

        Event::assertDispatched(DocumentCandidateValidationStarted::class);
        Event::assertDispatched(DocumentCandidateValidationCompleted::class);
        Event::assertDispatched(DocumentCandidateValidationRequiresReview::class);
        Event::assertDispatched(DocumentCandidateCriticalDivergenceDetected::class);
    }

    public function test_field_extraction_dispatches_candidate_validation_job_for_application_documents(): void
    {
        Queue::fake();
        config(['document-ai-extraction.ollama.enabled' => false]);

        [$application, $analysis] = $this->applicationWithClassifiedIrsText(
            (string) file_get_contents(base_path('tests/Fixtures/document-intelligence/extraction/irs.txt'))
        );

        app(DocumentFieldExtractionPipeline::class)->process($analysis);

        Queue::assertPushed(
            ValidateDocumentAiAgainstApplicationJob::class,
            fn (ValidateDocumentAiAgainstApplicationJob $job): bool => $job->documentAiAnalysisId === $analysis->id
                && $job->applicationId === $application->id
        );
    }

    /**
     * @return array{0: Application, 1: DocumentAiAnalysis}
     */
    private function applicationWithExtractedIrs(string $grossIncome): array
    {
        [$application, $submission] = $this->applicationWithSubmission();
        $analysis = $this->analysisForSubmission($submission, DocumentAiDocumentType::Irs);

        $this->field($analysis, 'taxpayer_name', 'Sujeito passivo', 'Maria Silva Correia', DocumentAiExtractedFieldType::String);
        $this->field($analysis, 'nif', 'NIF', '123456789', DocumentAiExtractedFieldType::Identifier);
        $this->field($analysis, 'gross_income', 'Rendimento global', $grossIncome, DocumentAiExtractedFieldType::Money);

        return [$application, $analysis->fresh('fields') ?? $analysis];
    }

    /**
     * @return array{0: Application, 1: DocumentAiAnalysis}
     */
    private function applicationWithClassifiedIrsText(string $ocrText): array
    {
        [$application, $submission] = $this->applicationWithSubmission();
        $analysis = $this->analysisForSubmission($submission, DocumentAiDocumentType::Irs, [
            'ocr_text' => $ocrText,
            'raw_text' => $ocrText,
        ]);

        return [$application, $analysis];
    }

    /**
     * @return array{0: Application, 1: DocumentSubmission}
     */
    private function applicationWithSubmission(): array
    {
        $user = User::factory()->create(['name' => 'Maria Silva Correia']);
        $registration = AdhesionRegistration::factory()->registered()->create([
            'user_id' => $user->id,
            'full_name' => 'Maria Silva Correia',
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
            'full_name' => 'Maria Silva Correia',
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
            'current_address' => 'Rua das Flores 10',
            'current_city' => 'Alcanena',
            'current_municipality' => 'Alcanena',
            'current_monthly_rent' => 450,
        ]);
        $application = Application::factory()->submitted()->create([
            'user_id' => $user->id,
            'adhesion_registration_id' => $registration->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $housing->id,
        ]);
        $submission = DocumentSubmission::factory()->create([
            'user_id' => $user->id,
            'adhesion_registration_id' => $registration->id,
            'household_id' => $household->id,
            'application_id' => $application->id,
            'title' => 'IRS fictício de teste',
        ]);

        return [$application, $submission];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function analysisForSubmission(DocumentSubmission $submission, DocumentAiDocumentType $documentType, array $overrides = []): DocumentAiAnalysis
    {
        return DocumentAiAnalysis::factory()->completed()->create([
            'document_submission_id' => $submission->id,
            'status' => DocumentAiStatus::Completed,
            'ocr_status' => DocumentAiOcrStatus::Completed,
            'ocr_available' => true,
            'classification_status' => DocumentAiClassificationStatus::Completed,
            'detected_document_type' => $documentType,
            'detected_document_label' => $documentType->label(),
            'classification_confidence' => '0.96',
            'classification_requires_manual_review' => false,
            'extraction_status' => DocumentAiExtractionStatus::Completed,
            'extraction_schema_version' => '1.0',
            'extraction_confidence' => '0.92',
            'extraction_requires_manual_review' => false,
            ...$overrides,
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
