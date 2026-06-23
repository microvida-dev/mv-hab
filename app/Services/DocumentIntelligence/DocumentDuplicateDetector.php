<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiRiskFlag;
use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiRiskSeverity;
use App\Models\Application;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentSubmission;

class DocumentDuplicateDetector
{
    public function detect(DocumentAiAnalysis $analysis): ?DocumentAiRiskFlag
    {
        if (! is_string($analysis->source_sha256) || $analysis->source_sha256 === '') {
            return null;
        }

        $application = $this->resolveApplication($analysis);

        $query = DocumentAiAnalysis::query()
            ->where('source_sha256', $analysis->source_sha256)
            ->whereKeyNot($analysis->id);

        if ($application instanceof Application) {
            $query->whereHas('documentSubmission', function ($submissions) use ($application): void {
                $submissions->where('application_id', $application->id)
                    ->orWhereHas('applications', fn ($applications) => $applications->whereKey($application->id));
            });
        }

        $duplicates = $query->limit(5)->pluck('id')->all();

        if ($duplicates === []) {
            return null;
        }

        return new DocumentAiRiskFlag(
            code: DocumentAiRiskFlagCode::DuplicateDocument,
            severity: DocumentAiRiskSeverity::Medium,
            scoreImpact: (int) config('document-ai-score.penalties.duplicate_document', 15),
            message: 'Foi identificado outro documento com a mesma impressão técnica.',
            detectedBy: 'document_duplicate_detector',
            confidence: 0.95,
            suggestionTemplate: DocumentAiRiskFlagCode::DuplicateDocument->value,
            metadata: [
                'duplicate_analysis_ids' => array_map('intval', $duplicates),
            ],
        );
    }

    private function resolveApplication(DocumentAiAnalysis $analysis): ?Application
    {
        $analysis->loadMissing([
            'documentSubmission.application',
            'documentSubmission.applications',
            'documentSubmission.adhesionRegistration.applications',
        ]);

        $submission = $analysis->documentSubmission;

        if (! $submission instanceof DocumentSubmission) {
            return null;
        }

        if ($submission->application instanceof Application) {
            return $submission->application;
        }

        $application = $submission->applications->sortByDesc('created_at')->first();

        if ($application instanceof Application) {
            return $application;
        }

        return $submission->adhesionRegistration?->applications->sortByDesc('created_at')->first();
    }
}
