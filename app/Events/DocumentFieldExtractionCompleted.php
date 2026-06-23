<?php

namespace App\Events;

use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiExtractionStatus;
use Illuminate\Foundation\Events\Dispatchable;

class DocumentFieldExtractionCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly int $documentAiAnalysisId,
        public readonly DocumentAiDocumentType $documentType,
        public readonly DocumentAiExtractionStatus $status,
        public readonly float $confidence,
    ) {}
}
