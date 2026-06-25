<?php

namespace Tests\Feature;

use App\Models\WorkTask;
use App\Services\DocumentIntelligence\DocumentAiAssistantPipeline;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDocumentAiAssistantFixtures;
use Tests\TestCase;

class QA33AdvancedDocumentAiTest extends TestCase
{
    use CreatesDocumentAiAssistantFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->seed(MunicipalTeamSeeder::class);
    }

    public function test_qa33_assistant_flags_risk_creates_work_task_and_preserves_administrative_decision(): void
    {
        [$application,, $analysis] = $this->createAssistantAnalysis();
        $this->addNifDivergence($application, $analysis);
        $statusBefore = $application->status;

        $score = app(DocumentAiAssistantPipeline::class)->process($analysis->fresh(['validations', 'fields']) ?? $analysis);

        $this->assertTrue($score->requires_manual_review);
        $this->assertSame($statusBefore, $application->fresh()->status);
        $this->assertDatabaseHas('document_ai_flags', [
            'document_ai_analysis_id' => $analysis->id,
            'code' => 'nif_mismatch',
        ]);
        $this->assertDatabaseHas('document_ai_suggestions', [
            'document_ai_analysis_id' => $analysis->id,
            'flag_code' => 'nif_mismatch',
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('work_tasks', [
            'type' => WorkTask::TYPE_DOCUMENT_REVIEW,
            'source' => 'document_ai_risk:analysis:'.$analysis->id,
            'related_type' => $analysis->getMorphClass(),
            'related_id' => $analysis->id,
        ]);

        $task = WorkTask::query()
            ->where('source', 'document_ai_risk:analysis:'.$analysis->id)
            ->firstOrFail();

        $this->assertSame('red', $task->metadata['score_colour'] ?? null);
        $this->assertArrayNotHasKey('nif', $task->metadata ?? []);
        $this->assertArrayNotHasKey('morada', $task->metadata ?? []);
        $this->assertArrayNotHasKey('rendimento', $task->metadata ?? []);

        $this->assertDatabaseHas('audit_logs', ['action' => 'document_ai_validation_started']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document_ai_validation_completed']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document_ai_score_calculated']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document_ai_suggestion_created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document_ai_manual_review_required']);
    }
}
