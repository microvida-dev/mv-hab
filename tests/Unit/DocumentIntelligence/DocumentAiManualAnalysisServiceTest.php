<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Enums\DocumentAiStatus;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiScore;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\DocumentIntelligence\DocumentAiAssistantPipeline;
use App\Services\DocumentIntelligence\DocumentAiManualAnalysisService;
use App\Services\DocumentIntelligence\DocumentAiPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DocumentAiManualAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_processes_pending_analysis_and_creates_assistant_score(): void
    {
        [$submission] = $this->documentSubmission();
        $actor = User::factory()->create();
        $analysis = DocumentAiAnalysis::factory()->create([
            'document_submission_id' => $submission->id,
            'document_version_id' => $submission->current_version_id,
            'status' => DocumentAiStatus::Pending,
        ]);

        $documentAiPipeline = Mockery::mock(DocumentAiPipeline::class);
        $documentAiPipeline->shouldReceive('createPendingForDocument')->never();
        $documentAiPipeline->shouldReceive('process')
            ->once()
            ->with(Mockery::on(fn (DocumentAiAnalysis $argument): bool => $argument->is($analysis)))
            ->andReturnUsing(function (DocumentAiAnalysis $argument): DocumentAiAnalysis {
                $argument->forceFill(['status' => DocumentAiStatus::Completed])->save();

                return $argument->fresh() ?? $argument;
            });

        $assistantPipeline = Mockery::mock(DocumentAiAssistantPipeline::class);
        $assistantPipeline->shouldReceive('process')
            ->once()
            ->with(
                Mockery::on(fn (DocumentAiAnalysis $argument): bool => $argument->is($analysis)),
                Mockery::on(fn (User $argument): bool => $argument->is($actor)),
            )
            ->andReturnUsing(fn (DocumentAiAnalysis $argument): DocumentAiScore => DocumentAiScore::factory()->create([
                'document_ai_analysis_id' => $argument->id,
                'document_submission_id' => $submission->id,
            ]));

        $processed = (new DocumentAiManualAnalysisService($documentAiPipeline, $assistantPipeline))
            ->execute($submission, $actor);

        $this->assertSame(DocumentAiStatus::Completed, $processed->status);
        $this->assertNotNull($processed->latestScore);
    }

    public function test_it_does_not_reprocess_completed_scored_analysis(): void
    {
        [$submission] = $this->documentSubmission();
        $actor = User::factory()->create();
        $analysis = DocumentAiAnalysis::factory()->completed()->create([
            'document_submission_id' => $submission->id,
            'document_version_id' => $submission->current_version_id,
        ]);
        DocumentAiScore::factory()->create([
            'document_ai_analysis_id' => $analysis->id,
            'document_submission_id' => $submission->id,
        ]);

        $documentAiPipeline = Mockery::mock(DocumentAiPipeline::class);
        $documentAiPipeline->shouldReceive('createPendingForDocument')->never();
        $documentAiPipeline->shouldReceive('process')->never();

        $assistantPipeline = Mockery::mock(DocumentAiAssistantPipeline::class);
        $assistantPipeline->shouldReceive('process')->never();

        $processed = (new DocumentAiManualAnalysisService($documentAiPipeline, $assistantPipeline))
            ->execute($submission, $actor);

        $this->assertTrue($processed->is($analysis));
        $this->assertSame(1, DocumentAiScore::query()->where('document_ai_analysis_id', $analysis->id)->count());
    }

    /**
     * @return array{0: DocumentSubmission, 1: DocumentVersion}
     */
    private function documentSubmission(): array
    {
        $submission = DocumentSubmission::factory()->create([
            'storage_disk' => 'local',
            'storage_path' => 'documents/test/manual-analysis.pdf',
        ]);
        $version = DocumentVersion::factory()->create([
            'document_submission_id' => $submission->id,
            'storage_disk' => 'local',
            'storage_path' => 'documents/test/manual-analysis.pdf',
        ]);
        $submission->forceFill([
            'current_version_id' => $version->id,
            'mime_type' => $version->mime_type,
            'file_size' => $version->file_size,
            'checksum' => $version->checksum,
        ])->save();

        return [$submission->fresh('currentVersion') ?? $submission, $version];
    }
}
