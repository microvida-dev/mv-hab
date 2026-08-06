<?php

declare(strict_types=1);

namespace App\Services\Visits;

use App\Contracts\Security\HumanVerificationVerifier;
use App\Enums\Security\HumanVerificationContext;

final class PublicVisitChallengeService
{
    public function __construct(
        private readonly HumanVerificationVerifier $verifier,
    ) {}

    public function enabled(): bool
    {
        return $this->verifier->enabled(
            HumanVerificationContext::PublicVisit,
        );
    }

    public function siteKey(): ?string
    {
        return $this->verifier->siteKey(
            HumanVerificationContext::PublicVisit,
        );
    }

    public function action(): string
    {
        return $this->verifier->action(
            HumanVerificationContext::PublicVisit,
        );
    }

    public function verify(?string $token, ?string $ipAddress): bool
    {
        return $this->verifier->verify(
            HumanVerificationContext::PublicVisit,
            $token,
            $ipAddress,
        )->successful;
    }
}
