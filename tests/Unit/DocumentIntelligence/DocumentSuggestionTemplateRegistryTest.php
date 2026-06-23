<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Enums\DocumentAiRiskFlagCode;
use App\Services\DocumentIntelligence\DocumentSuggestionTemplateRegistry;
use Tests\TestCase;

class DocumentSuggestionTemplateRegistryTest extends TestCase
{
    public function test_all_supported_flags_have_neutral_suggestion_templates(): void
    {
        $registry = app(DocumentSuggestionTemplateRegistry::class);

        foreach (DocumentAiRiskFlagCode::cases() as $code) {
            $template = mb_strtolower($registry->templateFor($code));

            $this->assertNotSame('', trim($template));
            $this->assertStringNotContainsString('falsas declarações', $template);
            $this->assertStringNotContainsString('fraude confirmada', $template);
        }
    }
}
