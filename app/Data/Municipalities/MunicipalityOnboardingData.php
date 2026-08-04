<?php

namespace App\Data\Municipalities;

final readonly class MunicipalityOnboardingData
{
    public function __construct(
        public int $actorId,
        public string $name,
        public string $code,
        public string $taxNumber,
        public string $contactEmail,
        public string $adminName,
        public string $adminEmail,
        public string $justification,
    ) {}
}
