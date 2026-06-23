<?php

namespace App\Jobs;

use App\Models\DocumentAiAnalysis;
use App\Services\DocumentIntelligence\DocumentAiPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessDocumentAiJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(public readonly int $documentAiAnalysisId) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(DocumentAiPipeline $pipeline): void
    {
        $analysis = DocumentAiAnalysis::query()->findOrFail($this->documentAiAnalysisId);

        $pipeline->process($analysis);
    }

    public function failed(Throwable $exception): void
    {
        $analysis = DocumentAiAnalysis::query()->find($this->documentAiAnalysisId);

        if ($analysis instanceof DocumentAiAnalysis) {
            app(DocumentAiPipeline::class)->markFailed($analysis, $exception);
        }
    }
}
