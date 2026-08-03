<?php

declare(strict_types=1);

namespace App\Data\Regulatory;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\LegalRegimeResolutionStatus;
use App\Models\AffordableRentRegulatoryProfile;
use Carbon\CarbonImmutable;

final readonly class LegalRegimeResolution
{
    public function __construct(
        public LegalRegimeResolutionStatus $status,
        public ?AffordableRentLegalRegime $regime,
        public ?AffordableRentRegulatoryProfile $profile,
        public ?CarbonImmutable $referenceDate,
        public string $reason,
    ) {}

    public function isResolved(): bool
    {
        return $this->status === LegalRegimeResolutionStatus::Resolved;
    }
}
