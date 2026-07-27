<?php

declare(strict_types=1);

namespace App\Data\Regulatory;

use App\Enums\AnnualIncomeLimitStatus;

final readonly class AnnualIncomeLimitResult
{
    public function __construct(
        public AnnualIncomeLimitStatus $status,
        public int $householdSize,
        public ?string $householdFormulaLimit,
        public ?string $sixthIrsBracketLimit,
        public ?string $effectiveLimit,
        public ?int $taxYear,
        public ?string $sourceReference,
        public ?string $sourceVersion,
        public ?string $effectiveFrom,
        public ?string $effectiveUntil,
        public ?string $message = null,
    ) {}

    public function isConfigured(): bool
    {
        return $this->status === AnnualIncomeLimitStatus::Configured;
    }

    /**
     * @return array{
     *     status: string,
     *     household_size: int,
     *     household_formula_limit: string|null,
     *     sixth_irs_bracket_limit: string|null,
     *     effective_limit: string|null,
     *     tax_year: int|null,
     *     source_reference: string|null,
     *     source_version: string|null,
     *     effective_from: string|null,
     *     effective_until: string|null,
     *     message: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'household_size' => $this->householdSize,
            'household_formula_limit' => $this->householdFormulaLimit,
            'sixth_irs_bracket_limit' => $this->sixthIrsBracketLimit,
            'effective_limit' => $this->effectiveLimit,
            'tax_year' => $this->taxYear,
            'source_reference' => $this->sourceReference,
            'source_version' => $this->sourceVersion,
            'effective_from' => $this->effectiveFrom,
            'effective_until' => $this->effectiveUntil,
            'message' => $this->message,
        ];
    }
}
