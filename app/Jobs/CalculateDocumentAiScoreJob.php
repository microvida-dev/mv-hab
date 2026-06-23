<?php

namespace App\Jobs;

use App\Models\DocumentAiAnalysis;
use App\Services\DocumentIntelligence\DocumentAiAssistantPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculateDocumentAiScoreJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly int $documentAiAnalysisId,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(DocumentAiAssistantPipeline $pipeline): void
    {
        $analysis = DocumentAiAnalysis::query()->findOrFail($this->documentAiAnalysisId);

        $pipeline->process($analysis);
    }
}
