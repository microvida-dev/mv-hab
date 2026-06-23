<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DocumentCandidateValidationFailed
{
    use Dispatchable;

    public function __construct(
        public readonly ?int $documentAiValidationRunId,
        public readonly ?int $applicationId,
        public readonly ?int $documentAiAnalysisId,
        public readonly string $failureCode,
    ) {}
}
