<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiRiskFlag;
use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiRiskSeverity;
use App\Models\Application;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentSubmission;
use Illuminate\Database\Eloquent\Builder;

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

        if ($analysis->document_submission_id !== null) {
            $query->where(function (Builder $duplicates) use ($analysis): void {
                $duplicates
                    ->whereNull('document_submission_id')
                    ->orWhere('document_submission_id', '!=', $analysis->document_submission_id);
            });
        }

        if ($analysis->document_version_id !== null) {
            $query->where(function (Builder $duplicates) use ($analysis): void {
                $duplicates
                    ->whereNull('document_version_id')
                    ->orWhere('document_version_id', '!=', $analysis->document_version_id);
            });
        }

        if ($application instanceof Application) {
            $query->whereHas('documentSubmission', function (Builder $submissions) use ($application): void {
                $submissions->where('application_id', $application->id)
                    ->orWhereHas('applications', fn (Builder $applications) => $applications->whereKey($application->id));
            });
        }

        $analysis->loadMissing('documentSubmission');

        $currentSubmission = $analysis->documentSubmission;

        if (! $currentSubmission instanceof DocumentSubmission) {
            return null;
        }

        $duplicates = $query
            ->with('documentSubmission')
            ->latest('id')
            ->get()
            ->filter(function (DocumentAiAnalysis $duplicate) use ($currentSubmission): bool {
                $submission = $duplicate->documentSubmission;

                if (! $submission instanceof DocumentSubmission) {
                    return false;
                }

                return $this->isActionableDuplicate($currentSubmission, $submission);
            })
            ->take(10)
            ->pluck('id')
            ->all();

        if ($duplicates === []) {
            return null;
        }

        return new DocumentAiRiskFlag(
            code: DocumentAiRiskFlagCode::DuplicateDocument,
            severity: DocumentAiRiskSeverity::Medium,
            scoreImpact: (int) config('document-ai-score.penalties.duplicate_document', 15),
            message: 'Foi identificado outro documento com a mesma impressão técnica no mesmo contexto documental.',
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

    private function isActionableDuplicate(DocumentSubmission $current, DocumentSubmission $duplicate): bool
    {
        if ($current->adhesion_registration_id !== $duplicate->adhesion_registration_id) {
            return true;
        }

        if ($current->user_id !== null && $duplicate->user_id !== null && $current->user_id !== $duplicate->user_id) {
            return true;
        }

        return $current->required_document_id === $duplicate->required_document_id
            && $current->document_type_id === $duplicate->document_type_id
            && $this->sameTarget($current, $duplicate);
    }

    private function sameTarget(DocumentSubmission $current, DocumentSubmission $duplicate): bool
    {
        foreach ($this->targetColumns() as $column) {
            if ($current->getAttribute($column) !== $duplicate->getAttribute($column)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function targetColumns(): array
    {
        return [
            'application_id',
            'household_id',
            'household_member_id',
            'income_record_id',
            'current_housing_situation_id',
            'contract_id',
        ];
    }
}
