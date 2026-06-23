<?php

namespace App\Events;

use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiRiskSeverity;
use Illuminate\Foundation\Events\Dispatchable;

class DocumentAiRiskFlagDetected
{
    use Dispatchable;

    public function __construct(
        public readonly int $documentAiAnalysisId,
        public readonly DocumentAiRiskFlagCode $code,
        public readonly DocumentAiRiskSeverity $severity,
        public readonly int $scoreImpact,
    ) {}
}
