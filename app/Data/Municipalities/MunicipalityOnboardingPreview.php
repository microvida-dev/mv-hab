<?php

namespace App\Data\Municipalities;

final readonly class MunicipalityOnboardingPreview
{
    /**
     * @param  list<string>  $conflicts
     */
    public function __construct(
        public string $operationId,
        public string $inputFingerprint,
        public string $municipalityCode,
        public string $roleTemplateKey,
        public string $roleTemplateVersion,
        public string $roleTemplateFingerprint,
        public int $permissionCount,
        public bool $mfaRequired,
        public array $conflicts,
        public bool $idempotentReplay,
        public int $writes = 0,
        public int $entitlementsActivated = 0,
    ) {}

    public function hasConflicts(): bool
    {
        return $this->conflicts !== [];
    }
}
