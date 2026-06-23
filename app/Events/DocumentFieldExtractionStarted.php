<?php

namespace App\Events;

use App\Enums\DocumentAiDocumentType;
use Illuminate\Foundation\Events\Dispatchable;

class DocumentFieldExtractionStarted
{
    use Dispatchable;

    public function __construct(
        public readonly int $documentAiAnalysisId,
        public readonly DocumentAiDocumentType $documentType,
    ) {}
}
