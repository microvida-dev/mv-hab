<?php

namespace App\Events;

use App\Enums\DocumentAiScoreLabel;
use Illuminate\Foundation\Events\Dispatchable;

class DocumentAiManualReviewRecommended
{
    use Dispatchable;

    public function __construct(
        public readonly int $documentAiAnalysisId,
        public readonly int $documentAiScoreId,
        public readonly int $score,
        public readonly DocumentAiScoreLabel $label,
    ) {}
}
