<?php

namespace Tests\Feature\DocumentIntelligence;

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

class DocumentAiAssistantSuggestionTest extends TestCase
{
    use CreatesDocumentAiAssistantFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_technician_must_justify_accepting_or_dismissing_ai_suggestion(): void
    {
        $suggestion = $this->suggestion();
        $admin = $this->userWithRole('administrator');

        $this->actingAs($admin)
            ->post(route('backoffice.document-ai.assistant.suggestions.accept', $suggestion), [
                'confirm_accept' => '1',
            ])
            ->assertSessionHasErrors('accept_reason');

        $this->actingAs($admin)
            ->post(route('backoffice.document-ai.assistant.suggestions.accept', $suggestion), [
                'confirm_accept' => '1',
                'accept_reason' => 'Validação técnica humana registada.',
            ])
            ->assertRedirect(route('backoffice.document-ai.assistant.show', $suggestion->analysis));

        $this->assertDatabaseHas('document_ai_suggestions', [
            'id' => $suggestion->id,
            'status' => DocumentAiSuggestionStatus::Accepted->value,
            'metadata->accepted_reason' => 'Validação técnica humana registada.',
        ]);

        $this->actingAs($admin)
            ->post(route('backoffice.document-ai.assistant.suggestions.dismiss', $suggestion), [])
            ->assertSessionHasErrors('dismiss_reason');

        $this->actingAs($admin)
            ->post(route('backoffice.document-ai.assistant.suggestions.dismiss', $suggestion), [
                'dismiss_reason' => 'Sugestão verificada e sem impacto documental.',
            ])
            ->assertRedirect(route('backoffice.document-ai.assistant.show', $suggestion->analysis));

        $this->assertDatabaseHas('audit_logs', ['action' => 'document_ai_suggestion_accepted']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document_ai_suggestion_dismissed']);
    }

    private function suggestion(): DocumentAiSuggestion
    {
        [,, $analysis] = $this->createAssistantAnalysis();
        $score = DocumentAiScore::factory()->create([
            'document_ai_analysis_id' => $analysis->id,
            'document_submission_id' => $analysis->document_submission_id,
            'score' => 62,
            'label' => DocumentAiScoreLabel::RequerRevisao->value,
            'requires_manual_review' => true,
        ]);

        return DocumentAiSuggestion::factory()->create([
            'document_ai_analysis_id' => $analysis->id,
            'document_ai_score_id' => $score->id,
            'flag_code' => 'missing_required_fields',
            'severity' => DocumentAiRiskSeverity::High->value,
            'status' => DocumentAiSuggestionStatus::Draft->value,
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
