<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Enums\DocumentAiComparisonMethod;
use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiValidationSeverity;
use App\Enums\DocumentAiValidationStatus;
use App\Services\DocumentIntelligence\DocumentValidationComparator;
use App\Services\DocumentIntelligence\DocumentValidationRuleRegistry;
use App\Services\DocumentIntelligence\DocumentValidationSeverityResolver;
use Tests\TestCase;

class DocumentValidationServicesTest extends TestCase
{
    public function test_comparator_accepts_fuzzy_names_and_normalized_identifiers(): void
    {
        $comparator = app(DocumentValidationComparator::class);

        $name = $comparator->compare('Maria da Silva Correia', 'MARIA SILVA CORREIA', DocumentAiComparisonMethod::FuzzyName);
        $nif = $comparator->compare('123 456 789', '123456789', DocumentAiComparisonMethod::NormalizedExact);

        $this->assertContains($name['status'], [DocumentAiValidationStatus::Match, DocumentAiValidationStatus::PartialMatch]);
        $this->assertSame(DocumentAiValidationStatus::Match, $nif['status']);
    }

    public function test_money_divergence_is_escalated_when_document_income_is_higher(): void
    {
        config(['document-ai-validation.thresholds.critical_income_difference_percent' => 25]);

        $registry = app(DocumentValidationRuleRegistry::class);
        $rule = collect($registry->rulesFor(DocumentAiDocumentType::Irs))
            ->firstWhere('key', 'gross_income');
        $comparison = app(DocumentValidationComparator::class)
            ->compare(12000, 18000, DocumentAiComparisonMethod::MoneyTolerance);

        $this->assertNotNull($rule);
        $this->assertSame(DocumentAiValidationStatus::Mismatch, $comparison['status']);
        $this->assertSame(
            DocumentAiValidationSeverity::Critical,
            app(DocumentValidationSeverityResolver::class)->resolve($rule, $comparison['status'], $comparison['metadata'])
        );
    }

    public function test_registry_exposes_expected_rules_for_rent_contract(): void
    {
        $rules = app(DocumentValidationRuleRegistry::class)->rulesFor(DocumentAiDocumentType::ContratoArrendamento);

        $this->assertSame(['tenant', 'address', 'rent_amount'], collect($rules)->pluck('key')->all());
    }
}
