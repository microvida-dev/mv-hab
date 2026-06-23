<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentAnalysisFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $documentAiAnalysisId,
        public readonly string $failureCode,
    ) {}
}
