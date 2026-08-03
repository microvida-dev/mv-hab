<?php

namespace Tests\Unit\Regulatory;

use App\Enums\AnnualIncomeLimitStatus;
use App\Services\Regulatory\AnnualHouseholdIncomeLimitCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AnnualHouseholdIncomeLimitCalculatorTest extends TestCase
{
    #[DataProvider('householdFormulaCases')]
    public function test_effective_limit_is_the_lower_of_formula_and_fiscal_ceiling(
        int $householdSize,
        string $fiscalCeiling,
        string $expectedFormula,
        string $expectedEffective,
    ): void {
        $result = app(AnnualHouseholdIncomeLimitCalculator::class)->calculate(
            $householdSize,
            $this->parameters($fiscalCeiling),
            new CarbonImmutable('2026-07-15', 'Europe/Lisbon'),
        );

        $this->assertSame(AnnualIncomeLimitStatus::Configured, $result->status);
        $this->assertSame($expectedFormula, $result->householdFormulaLimit);
        $this->assertSame($fiscalCeiling, $result->sixthIrsBracketLimit);
        $this->assertSame($expectedEffective, $result->effectiveLimit);
        $this->assertIsString($result->effectiveLimit);
    }

    /**
     * @return iterable<string, array{int, string, string, string}>
     */
    public static function householdFormulaCases(): iterable
    {
        yield 'uma pessoa com teto fiscal inferior' => [1, '30000.00', '38632.00', '30000.00'];
        yield 'uma pessoa com fórmula inferior' => [1, '50000.00', '38632.00', '38632.00'];
        yield 'duas pessoas' => [2, '999999.00', '48632.00', '48632.00'];
        yield 'três pessoas' => [3, '999999.00', '53632.00', '53632.00'];
        yield 'sete pessoas' => [7, '999999.00', '73632.00', '73632.00'];
        yield 'fronteira decimal exata' => [1, '38631.99', '38632.00', '38631.99'];
    }

    public function test_missing_sixth_irs_bracket_source_is_fail_closed(): void
    {
        $parameters = $this->parameters('50000.00');
        $parameters['sixth_irs_bracket_upper_limit'] = null;
        $parameters['irs_source_reference'] = null;

        $result = app(AnnualHouseholdIncomeLimitCalculator::class)->calculate(
            2,
            $parameters,
            new CarbonImmutable('2026-07-15', 'Europe/Lisbon'),
        );

        $this->assertSame(
            AnnualIncomeLimitStatus::ConfigurationIncomplete,
            $result->status,
        );
        $this->assertNull($result->effectiveLimit);
        $this->assertSame('configuration_incomplete', $result->toArray()['status']);
    }

    public function test_tax_year_is_versioned_independently_from_reference_year(): void
    {
        $parameters = $this->parameters('50000.00');
        $parameters['tax_year'] = 2025;
        $parameters['irs_source_version'] = 'fixture-fiscal-2025';

        $result = app(AnnualHouseholdIncomeLimitCalculator::class)->calculate(
            1,
            $parameters,
            new CarbonImmutable('2026-07-15', 'Europe/Lisbon'),
        );

        $this->assertTrue($result->isConfigured());
        $this->assertSame(2025, $result->taxYear);
        $this->assertSame('fixture-fiscal-2025', $result->sourceVersion);
    }

    public function test_demo_source_is_rejected_outside_explicit_demo_mode(): void
    {
        config()->set('mvhab.regulatory_demo_mode', false);
        $parameters = $this->parameters('50000.00');
        $parameters['metadata'] = ['demo_only' => true];

        $result = app(AnnualHouseholdIncomeLimitCalculator::class)->calculate(
            1,
            $parameters,
            new CarbonImmutable('2026-07-15', 'Europe/Lisbon'),
        );

        $this->assertSame(
            AnnualIncomeLimitStatus::ConfigurationIncomplete,
            $result->status,
        );
        $this->assertNull($result->effectiveLimit);
    }

    /**
     * @return array<string, mixed>
     */
    private function parameters(string $fiscalCeiling): array
    {
        return [
            'annual_income_base_limit' => '38632.00',
            'second_person_increment' => '10000.00',
            'additional_person_increment' => '5000.00',
            'tax_year' => 2026,
            'sixth_irs_bracket_upper_limit' => $fiscalCeiling,
            'irs_source_reference' => 'TESTE-SEM-VALOR-JURIDICO',
            'irs_source_version' => 'fixture-fiscal-2026',
            'irs_effective_from' => '2026-01-01',
            'irs_effective_until' => '2026-12-31',
        ];
    }
}
