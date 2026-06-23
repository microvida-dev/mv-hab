<?php

namespace Tests\Feature\Backoffice;

use App\Enums\DocumentAiClassificationStatus;
use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiExtractedFieldType;
use App\Enums\DocumentAiExtractionSource;
use App\Enums\DocumentAiExtractionStatus;
use App\Enums\DocumentAiOcrStatus;
use App\Enums\DocumentAiStatus;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiField;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentAiExtractionPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_extraction_panel_is_protected_and_visible_to_backoffice_only(): void
    {
        $analysis = $this->extractedAnalysis();

        $this->get(route('backoffice.document-ai.extractions.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->userWithRole('candidate'))
            ->get(route('backoffice.document-ai.extractions.index'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('administrator'))
            ->get(route('backoffice.document-ai.extractions.index'))
            ->assertOk()
            ->assertSee('Extração IA documental')
            ->assertSee($analysis->detected_document_label);
    }

    public function test_detail_masks_sensitive_fields_for_regular_technicians_and_records_access(): void
    {
        $analysis = $this->extractedAnalysis();

        $this->actingAs($this->userWithRole('municipal_technician'))
            ->get(route('backoffice.document-ai.extractions.show', $analysis))
            ->assertOk()
            ->assertSee('Campos extraídos')
            ->assertDontSee('Contribuinte Ficticio')
            ->assertSee('***');

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $analysis->getMorphClass(),
            'auditable_id' => $analysis->id,
            'action' => 'document_ai_extraction_viewed',
        ]);
    }

    public function test_authorized_user_can_mark_extracted_field_for_review(): void
    {
        $analysis = $this->extractedAnalysis();
        $field = $analysis->fields()->where('key', 'taxpayer_name')->firstOrFail();

        $this->actingAs($this->userWithRole('administrator'))
            ->post(route('backoffice.document-ai.fields.review', $field), [
                'reason' => 'Confirmacao manual necessaria para teste.',
            ])
            ->assertRedirect(route('backoffice.document-ai.extractions.show', $analysis));

        $field->refresh();
        $analysis->refresh();

        $this->assertTrue($field->requires_review);
        $this->assertSame(DocumentAiExtractionStatus::ManualReview, $analysis->extraction_status);
        $this->assertTrue($analysis->extraction_requires_manual_review);
        $this->assertDatabaseHas('document_ai_flags', [
            'document_ai_analysis_id' => $analysis->id,
            'code' => 'field_manual_review_requested',
        ]);
    }

    private function extractedAnalysis(): DocumentAiAnalysis
    {
        $analysis = DocumentAiAnalysis::factory()->completed()->create([
            'status' => DocumentAiStatus::Completed,
            'ocr_status' => DocumentAiOcrStatus::Completed,
            'ocr_available' => true,
            'classification_status' => DocumentAiClassificationStatus::Completed,
            'detected_document_type' => DocumentAiDocumentType::Irs,
            'detected_document_label' => DocumentAiDocumentType::Irs->label(),
            'classification_confidence' => '0.96',
            'classification_source' => 'ocr+keywords+layout',
            'classification_requires_manual_review' => false,
            'extraction_status' => DocumentAiExtractionStatus::Completed,
            'extraction_schema_version' => '1.0',
            'extraction_confidence' => '0.92',
            'extraction_requires_manual_review' => false,
        ]);

        DocumentAiField::factory()->create([
            'document_ai_analysis_id' => $analysis->id,
            'document_type' => DocumentAiDocumentType::Irs->value,
            'key' => 'taxpayer_name',
            'label' => 'Sujeito passivo',
            'value' => 'Contribuinte Ficticio',
            'normalized_value' => 'Contribuinte Ficticio',
            'value_type' => DocumentAiExtractedFieldType::String->value,
            'confidence' => '0.92',
            'source' => DocumentAiExtractionSource::Regex->value,
            'requires_review' => false,
            'metadata' => ['category' => 'structured_extraction', 'sensitive' => true, 'health_data' => false],
        ]);

        DocumentAiField::factory()->create([
            'document_ai_analysis_id' => $analysis->id,
            'document_type' => DocumentAiDocumentType::Irs->value,
            'key' => 'fiscal_year',
            'label' => 'Ano fiscal',
            'value' => '2025',
            'normalized_value' => '2025',
            'value_type' => DocumentAiExtractedFieldType::Integer->value,
            'confidence' => '0.92',
            'source' => DocumentAiExtractionSource::Regex->value,
            'requires_review' => false,
            'metadata' => ['category' => 'structured_extraction', 'sensitive' => false, 'health_data' => false],
        ]);

        return $analysis->fresh('fields') ?? $analysis;
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $roleModel = Role::query()->where('name', $role)->firstOrFail();
        $user->roles()->attach($roleModel);

        return $user;
    }
}
