<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DocumentCandidateCriticalDivergenceDetected
{
    use Dispatchable;

    public function __construct(
        public readonly int $documentAiValidationId,
        public readonly int $applicationId,
        public readonly int $documentAiAnalysisId,
        public readonly string $validationGroup,
        public readonly string $validationKey,
    ) {}
}
