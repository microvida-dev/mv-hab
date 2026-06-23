<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentValidationCheckResult;
use App\Enums\DocumentAiValidationSeverity;
use App\Enums\DocumentAiValidationStatus;
use App\Models\Application;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiFlag;
use App\Models\DocumentAiProcessingLog;
use App\Models\DocumentAiValidation;
use App\Models\DocumentAiValidationRun;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DocumentValidationPersister
{
    public function startRun(Application $application, ?User $actor = null): DocumentAiValidationRun
    {
        $run = new DocumentAiValidationRun;
        $run->forceFill([
            'application_id' => $application->id,
            'status' => DocumentAiValidationStatus::Processing,
            'started_at' => now(),
            'created_by' => $actor?->id,
        ]);
        $run->save();

        return $run;
    }

    /**
     * @param  list<DocumentValidationCheckResult>  $results
     * @return list<DocumentAiValidation>
     */
    public function persist(DocumentAiValidationRun $run, DocumentAiAnalysis $analysis, array $results): array
    {
        return DB::transaction(function () use ($run, $analysis, $results): array {
            $persisted = [];

            foreach ($results as $result) {
                $validation = DocumentAiValidation::query()->firstOrNew([
                    'document_ai_analysis_id' => $analysis->id,
                    'application_id' => $run->application_id,
                    'validation_group' => $result->rule->group->value,
                    'validation_key' => $result->rule->key,
                ]);

                $validation->forceFill([
                    'document_ai_validation_run_id' => $run->id,
                    'document_ai_analysis_id' => $analysis->id,
                    'application_id' => $run->application_id,
                    'document_submission_id' => $analysis->document_submission_id,
                    'validation_group' => $result->rule->group,
                    'validation_key' => $result->rule->key,
                    'label' => $result->rule->label,
                    'status' => $result->status,
                    'severity' => $result->severity,
                    'confidence' => $result->confidence,
                    'candidate_value' => $this->plainValue($result->candidateValue),
                    'extracted_value' => $this->plainValue($result->extractedValue),
                    'candidate_value_hash' => $this->hashValue($result->candidateValue),
                    'extracted_value_hash' => $this->hashValue($result->extractedValue),
                    'value_type' => $result->rule->valueType,
                    'comparison_method' => $result->rule->method,
                    'message' => $result->message,
                    'recommendation' => $result->recommendation,
                    'requires_manual_review' => $result->requiresManualReview,
                    'metadata' => [
                        'sensitive' => $result->rule->sensitive,
                        'income' => $result->rule->income,
                        'health_data' => $result->rule->healthData,
                        'document_type' => $analysis->detected_document_type?->value,
                        ...$result->metadata,
                    ],
                ]);
                $validation->save();

                if ($result->requiresManualReview) {
                    $this->recordFlag($analysis, $validation, $result);
                }

                $persisted[] = $validation;
            }

            $this->recordProcessingLog($analysis, 'candidate_validation_results_persisted', 'info', 'Resultados de cruzamento com candidatura persistidos.', [
                'run_id' => $run->id,
                'checks_count' => count($results),
            ]);

            return $persisted;
        });
    }

    /**
     * @param  list<DocumentAiValidation>  $validations
     */
    public function completeRun(DocumentAiValidationRun $run, array $validations): DocumentAiValidationRun
    {
        $collection = collect($validations);
        $critical = $this->countSeverity($collection, DocumentAiValidationSeverity::Critical);
        $medium = $this->countSeverity($collection, DocumentAiValidationSeverity::Medium);
        $light = $this->countSeverity($collection, DocumentAiValidationSeverity::Light);
        $inconclusive = $collection
            ->filter(fn (DocumentAiValidation $validation): bool => in_array($validation->status, [
                DocumentAiValidationStatus::Inconclusive,
                DocumentAiValidationStatus::MissingCandidateValue,
                DocumentAiValidationStatus::MissingDocumentValue,
            ], true))
            ->count();
        $requiresReview = $critical > 0 || $medium > 0 || $collection->contains(fn (DocumentAiValidation $validation): bool => (bool) $validation->requires_manual_review);

        $run->forceFill([
            'status' => $requiresReview ? DocumentAiValidationStatus::ManualReview : DocumentAiValidationStatus::Completed,
            'total_checks' => $collection->count(),
            'matches_count' => $collection->filter(fn (DocumentAiValidation $validation): bool => $validation->status === DocumentAiValidationStatus::Match)->count(),
            'critical_count' => $critical,
            'medium_count' => $medium,
            'light_count' => $light,
            'inconclusive_count' => $inconclusive,
            'requires_manual_review' => $requiresReview,
            'completed_at' => now(),
            'failed_at' => null,
            'failure_reason' => null,
        ]);
        $run->save();

        return $run;
    }

    public function failRun(DocumentAiValidationRun $run, string $reason): DocumentAiValidationRun
    {
        $run->forceFill([
            'status' => DocumentAiValidationStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => $reason,
            'requires_manual_review' => true,
        ]);
        $run->save();

        return $run;
    }

    /**
     * @param  Collection<int, DocumentAiValidation>  $validations
     */
    private function countSeverity(Collection $validations, DocumentAiValidationSeverity $severity): int
    {
        return $validations->filter(fn (DocumentAiValidation $validation): bool => $validation->severity === $severity)->count();
    }

    private function plainValue(mixed $value): ?string
    {
        if (! (bool) config('document-ai-validation.store_plain_values', true)) {
            return null;
        }

        if ($value === null) {
            return null;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value) ?: null;
    }

    private function hashValue(mixed $value): ?string
    {
        if (! (bool) config('document-ai-validation.hash_values', true) || $value === null || $value === '') {
            return null;
        }

        $normalized = is_scalar($value) ? (string) $value : (json_encode($value) ?: '');

        return hash('sha256', trim(mb_strtolower($normalized)));
    }

    private function recordFlag(DocumentAiAnalysis $analysis, DocumentAiValidation $validation, DocumentValidationCheckResult $result): void
    {
        $severity = match ($result->severity) {
            DocumentAiValidationSeverity::Critical => 'high',
            DocumentAiValidationSeverity::Medium => 'medium',
            DocumentAiValidationSeverity::Light => 'low',
            DocumentAiValidationSeverity::None => 'info',
        };

        $flag = DocumentAiFlag::query()->firstOrNew([
            'document_ai_analysis_id' => $analysis->id,
            'code' => 'candidate_validation_'.$result->severity->value.'_'.$result->rule->key,
        ]);
        $flag->forceFill([
            'document_ai_analysis_id' => $analysis->id,
            'severity' => $severity,
            'message' => $result->message,
            'details' => [
                'category' => 'candidate_validation',
                'validation_id' => $validation->id,
                'validation_group' => $result->rule->group->value,
                'validation_key' => $result->rule->key,
                'status' => $result->status->value,
                'severity' => $result->severity->value,
            ],
            'requires_manual_review' => true,
        ]);
        $flag->save();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function recordProcessingLog(DocumentAiAnalysis $analysis, string $step, string $level, string $message, array $context): void
    {
        $log = new DocumentAiProcessingLog([
            'step' => $step,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
        $log->forceFill([
            'document_ai_analysis_id' => $analysis->id,
            'created_at' => now(),
        ]);
        $log->save();
    }
}
