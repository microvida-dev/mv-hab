<?php

namespace App\Data\Municipalities;

final readonly class InitialMunicipalityCatalogResult
{
    public function __construct(
        public int $municipalityId,
        public int $programId,
        public int $contestId,
        public string $programStatus,
        public string $contestStatus,
        public bool $idempotentReplay,
        public int $entitlementsActivated = 0,
    ) {}
}
