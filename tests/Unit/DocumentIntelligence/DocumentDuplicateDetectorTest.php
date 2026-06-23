<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Enums\DocumentAiRiskFlagCode;
use App\Models\DocumentAiAnalysis;
use App\Services\DocumentIntelligence\DocumentDuplicateDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDocumentAiAssistantFixtures;
use Tests\TestCase;

class DocumentDuplicateDetectorTest extends TestCase
{
    use CreatesDocumentAiAssistantFixtures;
    use RefreshDatabase;

    public function test_it_detects_duplicate_hash_inside_same_application_scope(): void
    {
        [, $submission, $analysis] = $this->createAssistantAnalysis([
            'source_sha256' => hash('sha256', 'duplicate-fixture'),
        ]);
        DocumentAiAnalysis::factory()->completed()->create([
            'document_submission_id' => $submission->id,
            'source_sha256' => $analysis->source_sha256,
        ]);

        $flag = app(DocumentDuplicateDetector::class)->detect($analysis);

        $this->assertNotNull($flag);
        $this->assertSame(DocumentAiRiskFlagCode::DuplicateDocument, $flag->code);
    }
}
