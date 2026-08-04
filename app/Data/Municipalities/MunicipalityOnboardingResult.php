<?php

namespace App\Data\Municipalities;

final readonly class MunicipalityOnboardingResult
{
    public function __construct(
        public string $operationId,
        public int $runId,
        public int $municipalityId,
        public int $adminUserId,
        public int $roleId,
        public int $invitationId,
        public string $invitationStatus,
        public bool $mfaRequired,
        public bool $idempotentReplay,
        public int $entitlementsActivated = 0,
    ) {}
}
