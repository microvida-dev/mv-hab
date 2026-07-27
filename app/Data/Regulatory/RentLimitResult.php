<?php

declare(strict_types=1);

namespace App\Data\Regulatory;

use App\Enums\RentLimitConfigurationStatus;

final readonly class RentLimitResult
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public RentLimitConfigurationStatus $status,
        public ?string $minimumRent,
        public ?string $maximumRent,
        public ?string $sourceVersion,
        public ?string $message,
        public array $parameters = [],
    ) {}

    public function isConfigured(): bool
    {
        return $this->status === RentLimitConfigurationStatus::Configured;
    }
}
