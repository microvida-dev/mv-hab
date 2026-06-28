<?php

namespace App\Services\DocumentIntelligence;

use App\Enums\DocumentAiStatus;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\User;

class DocumentAiManualAnalysisService
{
    public function __construct(
        private readonly DocumentAiPipeline $documentAiPipeline,
        private readonly DocumentAiAssistantPipeline $assistantPipeline,
    ) {}

    public function execute(DocumentSubmission $submission, User $actor): DocumentAiAnalysis
    {
        return $this->executeForSubmission($submission, $actor);
    }

    public function reprocess(DocumentAiAnalysis $analysis, User $actor): DocumentAiAnalysis
    {
        return $this->processAnalysis($analysis, $actor, forceDocumentPipeline: true, forceAssistantPipeline: true);
    }

    private function executeForSubmission(DocumentSubmission $submission, User $actor): DocumentAiAnalysis
    {
        $submission->loadMissing('currentVersion');

        $analysis = $this->analysisForCurrentVersion($submission)
            ?? $this->documentAiPipeline->createPendingForDocument($submission, $actor);

        return $this->processAnalysis($analysis, $actor);
    }

    private function processAnalysis(
        DocumentAiAnalysis $analysis,
        User $actor,
        bool $forceDocumentPipeline = false,
        bool $forceAssistantPipeline = false,
    ): DocumentAiAnalysis {
        $analysis = $analysis->fresh(['fields', 'flags', 'validations', 'latestScore', 'documentSubmission']) ?? $analysis;

        if ($this->shouldRunDocumentPipeline($analysis, $forceDocumentPipeline)) {
            $analysis = $this->documentAiPipeline->process($analysis);
            $analysis = $analysis->fresh(['fields', 'flags', 'validations', 'latestScore', 'documentSubmission']) ?? $analysis;
        }

        if ($this->shouldRunAssistantPipeline($analysis, $forceAssistantPipeline)) {
            $this->assistantPipeline->process($analysis, $actor);
            $analysis = $analysis->fresh(['fields', 'flags', 'validations', 'latestScore', 'documentSubmission']) ?? $analysis;
        }

        return $analysis;
    }

    private function analysisForCurrentVersion(DocumentSubmission $submission): ?DocumentAiAnalysis
    {
        $query = DocumentAiAnalysis::query()
            ->where('document_submission_id', $submission->id)
            ->with('latestScore')
            ->latest('id');

        if ($submission->currentVersion instanceof DocumentVersion) {
            $query->where('document_version_id', $submission->currentVersion->id);
        }

        return $query->first();
    }

    private function shouldRunDocumentPipeline(DocumentAiAnalysis $analysis, bool $forceDocumentPipeline): bool
    {
        if ($forceDocumentPipeline) {
            return $analysis->status !== DocumentAiStatus::Processing;
        }

        return in_array($analysis->status, [
            DocumentAiStatus::Pending,
            DocumentAiStatus::Failed,
        ], true);
    }

    private function shouldRunAssistantPipeline(DocumentAiAnalysis $analysis, bool $forceAssistantPipeline): bool
    {
        return (bool) config('document-ai-score.enabled', true)
            && ($forceAssistantPipeline || $analysis->latestScore === null)
            && in_array($analysis->status, [
                DocumentAiStatus::Completed,
                DocumentAiStatus::Failed,
                DocumentAiStatus::ManualReview,
            ], true);
    }
}
