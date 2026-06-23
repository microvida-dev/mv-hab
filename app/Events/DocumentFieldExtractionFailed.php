<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DocumentFieldExtractionFailed
{
    use Dispatchable;

    public function __construct(
        public readonly int $documentAiAnalysisId,
        public readonly string $failureCode,
    ) {}
}
