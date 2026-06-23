<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiRiskFlag;
use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiRiskSeverity;
use App\Enums\DocumentAiValidationSeverity;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiValidation;

class DocumentRiskFlagDetector
{
    public function __construct(
        private readonly DocumentQualityAnalyzer $qualityAnalyzer,
        private readonly DocumentDuplicateDetector $duplicateDetector,
    ) {}

    /**
     * @return list<DocumentAiRiskFlag>
     */
    public function detect(DocumentAiAnalysis $analysis): array
    {
        $analysis->loadMissing(['validations', 'fields']);

        $flags = [];

        foreach ($this->qualityAnalyzer->analyze($analysis) as $flag) {
            $flags[$flag->code->value] = $flag;
        }

        foreach ($this->validationFlags($analysis) as $flag) {
            $flags[$flag->code->value] = $flag;
        }

        $duplicate = $this->duplicateDetector->detect($analysis);
        if ($duplicate instanceof DocumentAiRiskFlag) {
            $flags[$duplicate->code->value] = $duplicate;
        }

        return array_values($flags);
    }

    /**
     * @return list<DocumentAiRiskFlag>
     */
    private function validationFlags(DocumentAiAnalysis $analysis): array
    {
        $flags = [];

        foreach ($analysis->validations as $validation) {
            if (! $validation->requires_manual_review) {
                continue;
            }

            $code = $this->codeForValidation($validation);

            if (! $code instanceof DocumentAiRiskFlagCode) {
                continue;
            }

            $flags[] = new DocumentAiRiskFlag(
                code: $code,
                severity: $this->severityForValidation($validation),
                scoreImpact: (int) config("document-ai-score.penalties.{$code->value}", 0),
                message: $this->messageForValidation($code),
                detectedBy: 'candidate_document_validation',
                confidence: is_numeric($validation->confidence) ? (float) $validation->confidence : 0.80,
                suggestionTemplate: $code->value,
                metadata: [
                    'validation_id' => $validation->id,
                    'validation_key' => $validation->validation_key,
                    'validation_group' => $validation->validation_group->value,
                ],
            );
        }

        return $flags;
    }

    private function codeForValidation(DocumentAiValidation $validation): ?DocumentAiRiskFlagCode
    {
        $key = mb_strtolower($validation->validation_key);

        return match (true) {
            str_contains($key, 'nif') => DocumentAiRiskFlagCode::NifMismatch,
            str_contains($key, 'name'), str_contains($key, 'nome'), str_contains($key, 'taxpayer') => DocumentAiRiskFlagCode::NameMismatch,
            str_contains($key, 'income'), str_contains($key, 'rendimento'), str_contains($key, 'salary'), str_contains($key, 'salario') => DocumentAiRiskFlagCode::IncomeIncompatible,
            default => null,
        };
    }

    private function severityForValidation(DocumentAiValidation $validation): DocumentAiRiskSeverity
    {
        return match ($validation->severity) {
            DocumentAiValidationSeverity::Critical => DocumentAiRiskSeverity::Critical,
            DocumentAiValidationSeverity::Medium => DocumentAiRiskSeverity::High,
            DocumentAiValidationSeverity::Light => DocumentAiRiskSeverity::Medium,
            default => DocumentAiRiskSeverity::Low,
        };
    }

    private function messageForValidation(DocumentAiRiskFlagCode $code): string
    {
        return match ($code) {
            DocumentAiRiskFlagCode::NifMismatch => 'Foi identificada divergência entre o NIF declarado e o documento analisado.',
            DocumentAiRiskFlagCode::NameMismatch => 'Foi identificada divergência entre o nome declarado e o documento analisado.',
            DocumentAiRiskFlagCode::IncomeIncompatible => 'Foram identificadas diferenças relevantes entre rendimentos declarados e dados documentais.',
            default => 'Foi identificada inconsistência que necessita de revisão manual.',
        };
    }
}
