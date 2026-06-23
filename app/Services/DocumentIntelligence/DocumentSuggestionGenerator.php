<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiRiskFlag;
use App\Data\DocumentIntelligence\DocumentAiSuggestionDraft;
use App\Enums\DocumentAiRiskSeverity;
use App\Enums\DocumentAiSuggestionStatus;

class DocumentSuggestionGenerator
{
    public function __construct(
        private readonly DocumentSuggestionTemplateRegistry $templates,
    ) {}

    /**
     * @param  list<DocumentAiRiskFlag>  $flags
     * @return list<DocumentAiSuggestionDraft>
     */
    public function generate(array $flags): array
    {
        $status = DocumentAiSuggestionStatus::tryFrom((string) config('document-ai-score.suggestions.default_status', 'draft'))
            ?? DocumentAiSuggestionStatus::Draft;
        $suggestions = [];

        foreach ($flags as $flag) {
            if ($flag->severity === DocumentAiRiskSeverity::Info) {
                continue;
            }

            $suggestions[] = new DocumentAiSuggestionDraft(
                flagCode: $flag->code,
                severity: $flag->severity,
                suggestion: $this->templates->templateFor($flag->code),
                status: $status,
                metadata: [
                    'source' => 'document_ai_assistant',
                    'auto_send' => false,
                    'detected_by' => $flag->detectedBy,
                ],
            );
        }

        return $suggestions;
    }
}
