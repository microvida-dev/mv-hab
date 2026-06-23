<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentOcrStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly int $documentAiAnalysisId) {}
}
