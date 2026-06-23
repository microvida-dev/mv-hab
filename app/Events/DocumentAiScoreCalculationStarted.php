<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DocumentAiScoreCalculationStarted
{
    use Dispatchable;

    public function __construct(
        public readonly int $documentAiAnalysisId,
    ) {}
}
