<?php

namespace Tests\Feature\DocumentIntelligence;

use App\Events\DocumentAiManualReviewRecommended;
use App\Events\DocumentAiRiskFlagDetected;
use App\Events\DocumentAiScoreCalculated;
use App\Jobs\CalculateDocumentAiScoreJob;
use App\Services\DocumentIntelligence\DocumentAiAssistantPipeline;
use App\Services\DocumentIntelligence\DocumentCandidateValidationPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CreatesDocumentAiAssistantFixtures;
use Tests\TestCase;

class DocumentAiAssistantIntegrationTest extends TestCase
{
    use CreatesDocumentAiAssistantFixtures;
    use RefreshDatabase;

    public function test_candidate_validation_dispatches_score_job_without_serializing_sensitive_payload(): void
    {
        Queue::fake();

        [$application,, $analysis] = $this->createAssistantAnalysis();

        app(DocumentCandidateValidationPipeline::class)->processAnalysis($analysis, $application);

        Queue::assertPushed(CalculateDocumentAiScoreJob::class, function (CalculateDocumentAiScoreJob $job) use ($analysis): bool {
            return $job->documentAiAnalysisId === $analysis->id
                && ! property_exists($job, 'ocrText')
                && ! property_exists($job, 'rawAiJson');
        });
    }

    public function test_assistant_pipeline_emits_events_and_keeps_final_decision_with_technicians(): void
    {
        Event::fake([
            DocumentAiScoreCalculated::class,
            DocumentAiRiskFlagDetected::class,
            DocumentAiManualReviewRecommended::class,
        ]);

        [$application,, $analysis] = $this->createAssistantAnalysis();
        $this->addNifDivergence($application, $analysis);
        $statusBefore = $application->status;

        $score = app(DocumentAiAssistantPipeline::class)->process($analysis->fresh(['validations', 'fields']) ?? $analysis);

        $this->assertTrue($score->requires_manual_review);
        $this->assertSame($statusBefore, $application->fresh()->status);
        $this->assertDatabaseMissing('applications', [
            'id' => $application->id,
            'status' => 'rejected',
        ]);

        Event::assertDispatched(DocumentAiScoreCalculated::class);
        Event::assertDispatched(DocumentAiRiskFlagDetected::class);
        Event::assertDispatched(DocumentAiManualReviewRecommended::class);
    }
}
