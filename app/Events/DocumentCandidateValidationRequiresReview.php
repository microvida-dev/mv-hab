<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DocumentCandidateValidationRequiresReview
{
    use Dispatchable;

    public function __construct(
        public readonly int $documentAiValidationRunId,
        public readonly int $applicationId,
        public readonly int $criticalCount,
        public readonly int $mediumCount,
    ) {}
}
