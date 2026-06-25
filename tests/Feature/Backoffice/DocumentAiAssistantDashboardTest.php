<?php

namespace Tests\Feature\Backoffice;

use App\Enums\DocumentAiRiskSeverity;
use App\Enums\DocumentAiScoreLabel;
use App\Enums\DocumentAiSuggestionStatus;
use App\Models\DocumentAiScore;
use App\Models\DocumentAiSuggestion;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDocumentAiAssistantFixtures;
use Tests\TestCase;

class DocumentAiAssistantDashboardTest extends TestCase
{
    use CreatesDocumentAiAssistantFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_assistant_panel_is_protected_and_visible_to_backoffice_only(): void
    {
        $score = $this->scoreRecord();

        $this->get(route('backoffice.document-ai.assistant.index'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->userWithRole('candidate'))
            ->get(route('backoffice.document-ai.assistant.index'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('administrator'))
            ->get(route('backoffice.document-ai.assistant.index'))
            ->assertOk()
            ->assertSee('Assistente IA documental')
            ->assertSee((string) $score->score)
            ->assertSee('O score IA e as flags são auxiliares');
    }

    public function test_detail_shows_assistant_signals_without_raw_ai_payloads(): void
    {
        $score = $this->scoreRecord();

        $this->actingAs($this->userWithRole('administrator'))
            ->get(route('backoffice.document-ai.assistant.show', $score->analysis))
            ->assertOk()
            ->assertSee('Score IA')
            ->assertSee('OCR Excelente')
            ->assertSee('Classificação correta')
            ->assertSee('Divergência rendimento')
            ->assertSee('Rever manualmente')
            ->assertDontSee('raw_ai_json')
            ->assertDontSee((string) $score->analysis->source_path);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $score->analysis->getMorphClass(),
            'auditable_id' => $score->analysis->id,
            'action' => 'document_ai_assistant_viewed',
        ]);
    }

    public function test_authorized_user_can_edit_accept_and_dismiss_internal_suggestions(): void
    {
        $score = $this->scoreRecord();
        $suggestion = $score->suggestions()->firstOrFail();
        $admin = $this->userWithRole('administrator');

        $this->actingAs($admin)
            ->put(route('backoffice.document-ai.assistant.suggestions.update', $suggestion), [
                'suggestion' => 'Solicita-se análise técnica complementar antes de decidir sobre o documento.',
            ])
            ->assertRedirect(route('backoffice.document-ai.assistant.show', $suggestion->analysis));

        $this->assertDatabaseHas('document_ai_suggestions', [
            'id' => $suggestion->id,
            'status' => DocumentAiSuggestionStatus::Edited->value,
        ]);

        $this->actingAs($admin)
            ->post(route('backoffice.document-ai.assistant.suggestions.accept', $suggestion), [
                'confirm_accept' => '1',
                'accept_reason' => 'Justificação técnica suficiente para aceitar a sugestão.',
            ])
            ->assertRedirect(route('backoffice.document-ai.assistant.show', $suggestion->analysis));

        $this->assertDatabaseHas('document_ai_suggestions', [
            'id' => $suggestion->id,
            'status' => DocumentAiSuggestionStatus::Accepted->value,
            'accepted_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('backoffice.document-ai.assistant.suggestions.dismiss', $suggestion), [
                'dismiss_reason' => 'Resolvido por revisão técnica.',
            ])
            ->assertRedirect(route('backoffice.document-ai.assistant.show', $suggestion->analysis));

        $this->assertDatabaseHas('document_ai_suggestions', [
            'id' => $suggestion->id,
            'status' => DocumentAiSuggestionStatus::Dismissed->value,
            'dismissed_by' => $admin->id,
        ]);
    }

    private function scoreRecord(): DocumentAiScore
    {
        [,, $analysis] = $this->createAssistantAnalysis();
        $score = DocumentAiScore::factory()->create([
            'document_ai_analysis_id' => $analysis->id,
            'document_submission_id' => $analysis->document_submission_id,
            'application_id' => $analysis->documentSubmission?->application_id,
            'score' => 72,
            'label' => DocumentAiScoreLabel::RequerRevisao->value,
            'requires_manual_review' => true,
            'explanation' => [
                'positives' => ['OCR Excelente', 'Classificação correta'],
                'attention' => ['Divergência rendimento'],
                'recommendations' => ['Rever manualmente'],
            ],
        ]);
        DocumentAiSuggestion::factory()->create([
            'document_ai_analysis_id' => $analysis->id,
            'document_ai_score_id' => $score->id,
            'application_id' => $score->application_id,
            'flag_code' => 'income_incompatible',
            'severity' => DocumentAiRiskSeverity::High->value,
            'status' => DocumentAiSuggestionStatus::Draft->value,
            'suggestion' => 'Foram identificadas diferenças entre rendimentos declarados e dados documentais. Solicita-se análise técnica.',
        ]);

        return $score->fresh(['analysis', 'suggestions']) ?? $score;
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $roleModel = Role::query()->where('name', $role)->firstOrFail();
        $user->roles()->attach($roleModel);

        return $user;
    }
}
