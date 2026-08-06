<?php

declare(strict_types=1);

namespace App\Data\Security;

use App\Enums\Security\HumanVerificationFailureReason;

final readonly class HumanVerificationResult
{
    /**
     * @param  list<string>  $errorCodes
     */
    public function __construct(
        public bool $successful,
        public ?HumanVerificationFailureReason $failureReason = null,
        public ?string $hostname = null,
        public ?string $action = null,
        public array $errorCodes = [],
    ) {}

    public static function success(
        ?string $hostname = null,
        ?string $action = null,
    ): self {
        return new self(
            successful: true,
            hostname: $hostname,
            action: $action,
        );
    }

    /**
     * @param  list<string>  $errorCodes
     */
    public static function failure(
        HumanVerificationFailureReason $reason,
        ?string $hostname = null,
        ?string $action = null,
        array $errorCodes = [],
    ): self {
        return new self(
            successful: false,
            failureReason: $reason,
            hostname: $hostname,
            action: $action,
            errorCodes: $errorCodes,
        );
    }
}
