<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\DocumentAiAnalysis;
use App\Services\DocumentIntelligence\DocumentCandidateValidationPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ValidateDocumentAiAgainstApplicationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public readonly int $documentAiAnalysisId,
        public readonly ?int $applicationId = null,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(DocumentCandidateValidationPipeline $pipeline): void
    {
        $analysis = DocumentAiAnalysis::query()->findOrFail($this->documentAiAnalysisId);
        $application = $this->applicationId !== null
            ? Application::query()->findOrFail($this->applicationId)
            : null;

        $pipeline->processAnalysis($analysis, $application);
    }
}
