<?php

namespace App\Services\Regulatory;

use App\Data\Regulatory\AnnualIncomeLimitResult;
use App\Enums\AnnualIncomeLimitStatus;
use App\Support\DecimalMoney;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Throwable;

final class AnnualHouseholdIncomeLimitCalculator
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function calculate(
        int $householdSize,
        array $parameters,
        CarbonInterface $referenceDate,
    ): AnnualIncomeLimitResult {
        $base = $this->decimal($parameters['annual_income_base_limit'] ?? null);
        $secondPerson = $this->decimal($parameters['second_person_increment'] ?? null);
        $additionalPerson = $this->decimal($parameters['additional_person_increment'] ?? null);
        $sixthBracket = $this->decimal($parameters['sixth_irs_bracket_upper_limit'] ?? null);
        $taxYear = $this->integer($parameters['tax_year'] ?? null);
        $sourceReference = $this->string($parameters['irs_source_reference'] ?? null);
        $sourceVersion = $this->string($parameters['irs_source_version'] ?? null);
        $effectiveFrom = $this->date($parameters['irs_effective_from'] ?? null);
        $effectiveUntil = $this->date($parameters['irs_effective_until'] ?? null);
        $isDemoOnly = (bool) (
            $parameters['demo_only']
            ?? data_get($parameters, 'metadata.demo_only')
            ?? data_get($parameters, 'metadata.demo')
            ?? false
        );

        if ($householdSize < 1) {
            return $this->incomplete(
                $householdSize,
                'O agregado deve possuir pelo menos uma pessoa.',
                $taxYear,
                $sourceReference,
                $sourceVersion,
                $effectiveFrom,
                $effectiveUntil,
            );
        }

        if (
            $base === null
            || ($householdSize >= 2 && $secondPerson === null)
            || ($householdSize >= 3 && $additionalPerson === null)
            || $sixthBracket === null
            || $taxYear === null
            || $sourceReference === null
            || $sourceVersion === null
            || $effectiveFrom === null
        ) {
            return $this->incomplete(
                $householdSize,
                'A fonte fiscal do limite superior do 6.º escalão do IRS está incompleta.',
                $taxYear,
                $sourceReference,
                $sourceVersion,
                $effectiveFrom,
                $effectiveUntil,
            );
        }

        if ($isDemoOnly && ! config('mvhab.regulatory_demo_mode', false)) {
            return $this->incomplete(
                $householdSize,
                'Uma fonte fiscal de demonstração não é válida fora do modo demo explícito.',
                $taxYear,
                $sourceReference,
                $sourceVersion,
                $effectiveFrom,
                $effectiveUntil,
            );
        }

        $localReferenceDate = CarbonImmutable::instance($referenceDate)
            ->setTimezone('Europe/Lisbon')
            ->startOfDay();

        if (
            $localReferenceDate->lt($effectiveFrom)
            || ($effectiveUntil !== null && $localReferenceDate->gt($effectiveUntil))
        ) {
            return $this->incomplete(
                $householdSize,
                'Não existe uma fonte fiscal vigente para a data de referência.',
                $taxYear,
                $sourceReference,
                $sourceVersion,
                $effectiveFrom,
                $effectiveUntil,
            );
        }

        $formulaLimit = $base;

        if ($householdSize >= 2) {
            $formulaLimit = DecimalMoney::add($formulaLimit, $secondPerson);
        }

        if ($householdSize >= 3) {
            $formulaLimit = DecimalMoney::add(
                $formulaLimit,
                DecimalMoney::multiply($additionalPerson, $householdSize - 2),
            );
        }

        return new AnnualIncomeLimitResult(
            status: AnnualIncomeLimitStatus::Configured,
            householdSize: $householdSize,
            householdFormulaLimit: $formulaLimit,
            sixthIrsBracketLimit: $sixthBracket,
            effectiveLimit: DecimalMoney::min($formulaLimit, $sixthBracket),
            taxYear: $taxYear,
            sourceReference: $sourceReference,
            sourceVersion: $sourceVersion,
            effectiveFrom: $effectiveFrom->toDateString(),
            effectiveUntil: $effectiveUntil?->toDateString(),
        );
    }

    private function incomplete(
        int $householdSize,
        string $message,
        ?int $taxYear,
        ?string $sourceReference,
        ?string $sourceVersion,
        ?CarbonImmutable $effectiveFrom,
        ?CarbonImmutable $effectiveUntil,
    ): AnnualIncomeLimitResult {
        return new AnnualIncomeLimitResult(
            status: AnnualIncomeLimitStatus::ConfigurationIncomplete,
            householdSize: $householdSize,
            householdFormulaLimit: null,
            sixthIrsBracketLimit: null,
            effectiveLimit: null,
            taxYear: $taxYear,
            sourceReference: $sourceReference,
            sourceVersion: $sourceVersion,
            effectiveFrom: $effectiveFrom?->toDateString(),
            effectiveUntil: $effectiveUntil?->toDateString(),
            message: $message,
        );
    }

    private function decimal(mixed $value): ?string
    {
        return is_numeric($value)
            ? DecimalMoney::normalize((string) $value)
            : null;
    }

    private function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function string(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== ''
            ? trim($value)
            : null;
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::instance($value)->startOfDay();
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, 'Europe/Lisbon')->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
