<?php

namespace App\Events;

use App\Enums\DocumentAiStatus;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentAnalysisCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $documentAiAnalysisId,
        public readonly DocumentAiStatus $status,
    ) {}
}
