<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Enums\DocumentAiRiskFlagCode;
use App\Services\DocumentIntelligence\DocumentRiskFlagDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDocumentAiAssistantFixtures;
use Tests\TestCase;

class DocumentRiskFlagDetectorTest extends TestCase
{
    use CreatesDocumentAiAssistantFixtures;
    use RefreshDatabase;

    public function test_it_maps_candidate_validation_divergence_to_assistant_flag(): void
    {
        [$application,, $analysis] = $this->createAssistantAnalysis();
        $this->addNifDivergence($application, $analysis);

        $flags = app(DocumentRiskFlagDetector::class)->detect($analysis->fresh(['validations', 'fields']) ?? $analysis);

        $this->assertTrue(collect($flags)->contains(
            fn ($flag): bool => $flag->code === DocumentAiRiskFlagCode::NifMismatch
        ));
    }
}
