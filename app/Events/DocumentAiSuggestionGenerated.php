<?php

namespace App\Events;

use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiSuggestionStatus;
use Illuminate\Foundation\Events\Dispatchable;

class DocumentAiSuggestionGenerated
{
    use Dispatchable;

    public function __construct(
        public readonly int $documentAiAnalysisId,
        public readonly DocumentAiRiskFlagCode $flagCode,
        public readonly DocumentAiSuggestionStatus $status,
    ) {}
}
