<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentValidationRule;
use App\Enums\DocumentAiComparisonMethod;
use App\Enums\DocumentAiValidationGroup;
use App\Enums\DocumentAiValidationSeverity;
use App\Enums\DocumentAiValidationStatus;

class DocumentValidationSeverityResolver
{
    /**
     * @param  array<string, mixed>  $comparisonMetadata
     */
    public function resolve(DocumentValidationRule $rule, DocumentAiValidationStatus $status, array $comparisonMetadata): DocumentAiValidationSeverity
    {
        if ($status === DocumentAiValidationStatus::Match || $status === DocumentAiValidationStatus::NotApplicable) {
            return DocumentAiValidationSeverity::None;
        }

        if ($status === DocumentAiValidationStatus::PartialMatch) {
            return $rule->baseSeverity === DocumentAiValidationSeverity::Critical
                ? DocumentAiValidationSeverity::Medium
                : DocumentAiValidationSeverity::Light;
        }

        if (in_array($status, [DocumentAiValidationStatus::MissingCandidateValue, DocumentAiValidationStatus::MissingDocumentValue, DocumentAiValidationStatus::Inconclusive], true)) {
            return $rule->baseSeverity === DocumentAiValidationSeverity::Critical
                ? DocumentAiValidationSeverity::Medium
                : DocumentAiValidationSeverity::Light;
        }

        if ($rule->method === DocumentAiComparisonMethod::MoneyTolerance) {
            return $this->incomeSeverity($rule, $comparisonMetadata);
        }

        if ($rule->group === DocumentAiValidationGroup::Identification) {
            return $rule->baseSeverity;
        }

        return $rule->baseSeverity;
    }

    /**
     * @param  array<string, mixed>  $comparisonMetadata
     */
    private function incomeSeverity(DocumentValidationRule $rule, array $comparisonMetadata): DocumentAiValidationSeverity
    {
        $differencePercent = (float) ($comparisonMetadata['difference_percent'] ?? 0.0);
        $criticalDifference = (float) config('document-ai-validation.thresholds.critical_income_difference_percent', 25);
        $documentHigher = (bool) ($comparisonMetadata['document_higher_than_declared'] ?? false);

        if ($rule->baseSeverity === DocumentAiValidationSeverity::Critical && $documentHigher && $differencePercent >= $criticalDifference) {
            return DocumentAiValidationSeverity::Critical;
        }

        if ($differencePercent >= (float) config('document-ai-validation.thresholds.money_medium_tolerance_percent', 15)) {
            return $rule->baseSeverity === DocumentAiValidationSeverity::Critical
                ? DocumentAiValidationSeverity::Medium
                : DocumentAiValidationSeverity::Light;
        }

        return DocumentAiValidationSeverity::Light;
    }
}
