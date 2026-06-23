<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentExtractionFlag;
use App\Data\DocumentIntelligence\DocumentExtractionResult;
use App\Data\DocumentIntelligence\ExtractedDocumentField;
use App\Enums\DocumentAiExtractionStatus;
use App\Enums\DocumentAiStatus;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiField;
use App\Models\DocumentAiFlag;
use App\Models\DocumentAiProcessingLog;
use Illuminate\Support\Carbon;

class DocumentExtractionPersister
{
    public function persist(DocumentAiAnalysis $analysis, DocumentExtractionResult $result): DocumentAiAnalysis
    {
        $payload = $this->payload($analysis, $result);
        $wasAlreadyManualReview = $analysis->status === DocumentAiStatus::ManualReview;
        $status = ($wasAlreadyManualReview || $result->requiresManualReview) ? DocumentAiStatus::ManualReview : DocumentAiStatus::Completed;

        $analysis->forceFill([
            'status' => $status,
            'summary' => $this->summary($analysis, $result),
            'confidence' => $result->confidence,
            'completed_at' => now(),
            'manual_review_at' => ($wasAlreadyManualReview || $result->requiresManualReview) ? ($analysis->manual_review_at ?? now()) : $analysis->manual_review_at,
            'failure_reason' => $result->requiresManualReview
                ? 'Extração estruturada requer revisão manual.'
                : ($wasAlreadyManualReview ? $analysis->failure_reason : null),
            'extraction_status' => $result->status,
            'extraction_schema_version' => $result->schemaVersion,
            'extraction_json' => $payload,
            'extraction_confidence' => $result->confidence,
            'extraction_model' => (string) config('document-ai-extraction.ollama.model', 'gemma3:4b'),
            'extraction_prompt_version' => (string) config('document-ai-extraction.prompt_version', 'sprint29.field_extraction.v1'),
            'extraction_completed_at' => now(),
            'extraction_failed_at' => $result->status === DocumentAiExtractionStatus::Failed ? now() : null,
            'extraction_requires_manual_review' => $result->requiresManualReview,
            'raw_ai_json' => $this->mergedRawPayload($analysis, $payload),
        ])->save();

        $this->replaceFields($analysis, $result);
        $this->recordFlags($analysis, $result->flags);
        $this->log($analysis, $result);

        return $analysis->fresh(['fields', 'flags', 'processingLogs']) ?? $analysis;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(DocumentAiAnalysis $analysis, DocumentExtractionResult $result): array
    {
        $fields = [];

        foreach ($result->fields as $field) {
            $fields[$field->key] = [
                'label' => $field->label,
                'type' => $field->type->value,
                'value' => $field->value,
                'normalized_value' => $field->normalizedValue,
                'confidence' => $field->confidence,
                'source' => $field->source->value,
                'requires_review' => $field->requiresReview,
                'sensitive' => $field->sensitive,
                'health_data' => $field->healthData,
            ];
        }

        return [
            'schema_version' => $result->schemaVersion,
            'prompt_version' => (string) config('document-ai-extraction.prompt_version', 'sprint29.field_extraction.v1'),
            'document_type' => $result->documentType->value,
            'document_label' => $result->documentType->label(),
            'status' => $result->status->value,
            'confidence' => $result->confidence,
            'requires_manual_review' => $result->requiresManualReview,
            'source' => $result->source,
            'model' => (string) config('document-ai-extraction.ollama.model', 'gemma3:4b'),
            'document' => [
                'document_submission_id' => $analysis->document_submission_id,
                'document_version_id' => $analysis->document_version_id,
            ],
            'fields' => $fields,
            'flags' => array_map(
                static fn (DocumentExtractionFlag $flag): array => [
                    'code' => $flag->code,
                    'severity' => $flag->severity,
                    'message' => $flag->message,
                    'field' => $flag->field,
                    'details' => $flag->details,
                ],
                $result->flags
            ),
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function mergedRawPayload(DocumentAiAnalysis $analysis, array $payload): array
    {
        $raw = is_array($analysis->raw_ai_json) ? $analysis->raw_ai_json : [];
        $raw['schema_version'] = 'sprint29.structured_extraction.v1';
        $raw['extraction'] = $payload;
        $raw['generated_at'] = Carbon::now()->toIso8601String();

        return $raw;
    }

    private function replaceFields(DocumentAiAnalysis $analysis, DocumentExtractionResult $result): void
    {
        $keys = array_map(
            static fn (ExtractedDocumentField $field): string => $field->key,
            $result->fields
        );

        if ($keys !== []) {
            DocumentAiField::query()
                ->where('document_ai_analysis_id', $analysis->id)
                ->whereIn('key', $keys)
                ->delete();
        }

        foreach ($result->fields as $field) {
            $record = new DocumentAiField([
                'document_type' => $result->documentType->value,
                'key' => $field->key,
                'label' => $field->label,
                'value' => $this->stringValue($field->value),
                'normalized_value' => $this->stringValue($field->normalizedValue),
                'value_type' => $field->type->value,
                'confidence' => $field->confidence,
                'source' => $field->source->value,
                'requires_review' => $field->requiresReview,
                'page' => $field->page,
                'metadata' => [
                    'category' => 'structured_extraction',
                    'sensitive' => $field->sensitive,
                    'health_data' => $field->healthData,
                    'schema_version' => $result->schemaVersion,
                ],
            ]);
            $record->forceFill(['document_ai_analysis_id' => $analysis->id]);
            $record->save();
        }
    }

    /**
     * @param  list<DocumentExtractionFlag>  $flags
     */
    private function recordFlags(DocumentAiAnalysis $analysis, array $flags): void
    {
        foreach ($flags as $flag) {
            $record = new DocumentAiFlag([
                'code' => $flag->code,
                'severity' => $flag->severity,
                'message' => $flag->message,
                'details' => [
                    'category' => 'structured_extraction',
                    'field' => $flag->field,
                    ...$flag->details,
                ],
                'requires_manual_review' => in_array($flag->severity, ['medium', 'high', 'critical'], true),
            ]);
            $record->forceFill(['document_ai_analysis_id' => $analysis->id]);
            $record->save();
        }
    }

    private function log(DocumentAiAnalysis $analysis, DocumentExtractionResult $result): void
    {
        $log = new DocumentAiProcessingLog([
            'step' => $result->requiresManualReview ? 'field_extraction_manual_review' : 'field_extraction_completed',
            'level' => $result->requiresManualReview ? 'warning' : 'info',
            'message' => $result->requiresManualReview
                ? 'Extração estruturada concluída com revisão manual.'
                : 'Extração estruturada concluída.',
            'context' => [
                'schema_version' => $result->schemaVersion,
                'status' => $result->status->value,
                'fields_count' => count($result->fields),
                'flags_count' => count($result->flags),
            ],
        ]);
        $log->forceFill([
            'document_ai_analysis_id' => $analysis->id,
            'created_at' => now(),
        ]);
        $log->save();
    }

    private function summary(DocumentAiAnalysis $analysis, DocumentExtractionResult $result): string
    {
        $classification = $analysis->detected_document_label ?? $result->documentType->label();
        $suffix = $result->requiresManualReview ? ' Requer revisão manual de campos.' : '';

        return $classification.' com extração estruturada '.number_format($result->confidence * 100, 0).'%.'.$suffix;
    }

    private function stringValue(string|int|float|bool|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
