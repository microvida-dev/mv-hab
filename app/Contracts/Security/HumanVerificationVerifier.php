<?php

declare(strict_types=1);

namespace App\Contracts\Security;

use App\Data\Security\HumanVerificationResult;
use App\Enums\Security\HumanVerificationContext;

interface HumanVerificationVerifier
{
    public function enabled(HumanVerificationContext $context): bool;

    public function siteKey(HumanVerificationContext $context): ?string;

    public function action(HumanVerificationContext $context): string;

    public function verify(
        HumanVerificationContext $context,
        ?string $token,
        ?string $ipAddress,
    ): HumanVerificationResult;
}
