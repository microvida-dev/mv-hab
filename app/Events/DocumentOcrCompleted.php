<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentOcrCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $documentAiAnalysisId,
        public readonly bool $ocrAvailable,
    ) {}
}
