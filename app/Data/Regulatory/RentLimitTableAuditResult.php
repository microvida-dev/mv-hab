<?php

declare(strict_types=1);

namespace App\Data\Regulatory;

use App\Enums\RentLimitConfigurationStatus;

final readonly class RentLimitTableAuditResult
{
    /**
     * @param  list<string>  $municipalities
     * @param  list<string>  $typologies
     * @param  list<string>  $missingRows
     * @param  list<string>  $findings
     */
    public function __construct(
        public RentLimitConfigurationStatus $status,
        public ?int $manifestId,
        public ?int $rentRuleSetId,
        public ?string $sourceDocument,
        public ?string $sourceReference,
        public ?string $sourceVersion,
        public ?string $effectiveFrom,
        public ?string $effectiveUntil,
        public ?string $declaredChecksum,
        public ?string $calculatedChecksum,
        public int $declaredRowCount,
        public int $actualRowCount,
        public array $municipalities,
        public array $typologies,
        public array $missingRows,
        public array $findings,
        public ?string $minimumRent = null,
        public ?string $maximumRent = null,
        public bool $demoOnly = false,
    ) {}

    public function isConfigured(): bool
    {
        return $this->status === RentLimitConfigurationStatus::Configured;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'manifest_id' => $this->manifestId,
            'rent_rule_set_id' => $this->rentRuleSetId,
            'source_document' => $this->sourceDocument,
            'source_reference' => $this->sourceReference,
            'source_version' => $this->sourceVersion,
            'effective_from' => $this->effectiveFrom,
            'effective_until' => $this->effectiveUntil,
            'declared_checksum' => $this->declaredChecksum,
            'calculated_checksum' => $this->calculatedChecksum,
            'declared_row_count' => $this->declaredRowCount,
            'actual_row_count' => $this->actualRowCount,
            'municipalities' => $this->municipalities,
            'typologies' => $this->typologies,
            'missing_rows' => $this->missingRows,
            'findings' => $this->findings,
            'minimum_rent' => $this->minimumRent,
            'maximum_rent' => $this->maximumRent,
            'demo_only' => $this->demoOnly,
        ];
    }
}
