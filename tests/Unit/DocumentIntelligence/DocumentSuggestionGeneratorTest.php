<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiRiskFlag;
use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiRiskSeverity;
use App\Enums\DocumentAiSuggestionStatus;
use App\Services\DocumentIntelligence\DocumentSuggestionGenerator;
use Tests\TestCase;

class DocumentSuggestionGeneratorTest extends TestCase
{
    public function test_it_generates_draft_suggestions_and_never_auto_sends(): void
    {
        $suggestions = app(DocumentSuggestionGenerator::class)->generate([
            new DocumentAiRiskFlag(DocumentAiRiskFlagCode::NifMismatch, DocumentAiRiskSeverity::Critical, 45, 'Divergência de teste.', 'test', 0.92),
        ]);

        $this->assertCount(1, $suggestions);
        $this->assertSame(DocumentAiSuggestionStatus::Draft, $suggestions[0]->status);
        $this->assertFalse($suggestions[0]->metadata['auto_send']);
        $this->assertStringContainsString('divergência', mb_strtolower($suggestions[0]->suggestion));
        $this->assertStringNotContainsString('fraude', mb_strtolower($suggestions[0]->suggestion));
    }
}
