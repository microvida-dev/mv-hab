<?php

namespace Tests\Feature\DocumentIntelligence;

use App\Enums\DocumentAiClassificationStatus;
use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiOcrStatus;
use App\Enums\DocumentAiStatus;
use App\Models\DocumentAiAnalysis;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentAiClassificationPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_classification_panel_is_protected_and_visible_to_backoffice_only(): void
    {
        $analysis = $this->classifiedAnalysis();

        $this->get(route('backoffice.document-ai.classifications.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->userWithRole('candidate'))
            ->get(route('backoffice.document-ai.classifications.index'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('administrator'))
            ->get(route('backoffice.document-ai.classifications.index'))
            ->assertOk()
            ->assertSee('Classificação IA documental')
            ->assertSee($analysis->detected_document_label);
    }

    public function test_detail_hides_ocr_text_without_audit_permission_and_records_access(): void
    {
        $analysis = $this->classifiedAnalysis([
            'ocr_text' => 'Texto OCR sensivel Documento Exemplo',
        ]);

        $this->actingAs($this->userWithRole('municipal_technician'))
            ->get(route('backoffice.document-ai.classifications.show', $analysis))
            ->assertOk()
            ->assertSee('Texto OCR sensível oculto')
            ->assertDontSee('Texto OCR sensivel Documento Exemplo');

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $analysis->getMorphClass(),
            'auditable_id' => $analysis->id,
            'action' => 'document_ai_classification_viewed',
        ]);
    }

    public function test_authorized_user_can_mark_analysis_for_manual_review(): void
    {
        $analysis = $this->classifiedAnalysis();

        $this->actingAs($this->userWithRole('administrator'))
            ->post(route('backoffice.document-ai.classifications.manual-review', $analysis), [
                'reason' => 'Confirmacao manual necessaria para teste.',
            ])
            ->assertRedirect(route('backoffice.document-ai.classifications.show', $analysis));

        $analysis->refresh();

        $this->assertSame(DocumentAiStatus::ManualReview, $analysis->status);
        $this->assertSame(DocumentAiClassificationStatus::ManualReview, $analysis->classification_status);
        $this->assertTrue($analysis->classification_requires_manual_review);
        $this->assertDatabaseHas('document_ai_flags', [
            'document_ai_analysis_id' => $analysis->id,
            'code' => 'manual_review_requested',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function classifiedAnalysis(array $overrides = []): DocumentAiAnalysis
    {
        return DocumentAiAnalysis::factory()->completed()->create([
            'status' => DocumentAiStatus::Completed,
            'ocr_status' => DocumentAiOcrStatus::Completed,
            'ocr_available' => true,
            'ocr_text' => 'Comprovativo de IBAN PT50000000000000000000000',
            'classification_status' => DocumentAiClassificationStatus::Completed,
            'detected_document_type' => DocumentAiDocumentType::Iban,
            'detected_document_label' => DocumentAiDocumentType::Iban->label(),
            'classification_confidence' => '0.96',
            'classification_source' => 'ocr+keywords+layout',
            'classification_signals' => ['keyword:iban', 'layout:iban_pattern'],
            'classification_requires_manual_review' => false,
            ...$overrides,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $roleModel = Role::query()->where('name', $role)->firstOrFail();
        $user->roles()->attach($roleModel);

        return $user;
    }
}
