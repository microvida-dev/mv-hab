<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DocumentCandidateValidationStarted
{
    use Dispatchable;

    public function __construct(
        public readonly int $documentAiValidationRunId,
        public readonly int $applicationId,
        public readonly ?int $documentAiAnalysisId = null,
    ) {}
}
