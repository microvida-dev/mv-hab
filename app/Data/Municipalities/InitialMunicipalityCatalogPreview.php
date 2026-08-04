<?php

namespace App\Data\Municipalities;

final readonly class InitialMunicipalityCatalogPreview
{
    /**
     * @param  list<string>  $conflicts
     */
    public function __construct(
        public string $municipalityCode,
        public string $profile,
        public string $fingerprint,
        public string $programSlug,
        public string $contestCode,
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
