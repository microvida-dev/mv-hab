<?php

namespace App\Events;

use App\Enums\DocumentAiDocumentType;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentClassificationRequiresReview
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $documentAiAnalysisId,
        public readonly DocumentAiDocumentType $documentType,
        public readonly float $confidence,
    ) {}
}
