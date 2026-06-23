<?php

namespace App\Events;

use App\Enums\DocumentAiValidationStatus;
use Illuminate\Foundation\Events\Dispatchable;

class DocumentCandidateValidationCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly int $documentAiValidationRunId,
        public readonly int $applicationId,
        public readonly DocumentAiValidationStatus $status,
        public readonly int $totalChecks,
    ) {}
}
