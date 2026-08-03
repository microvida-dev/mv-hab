<?php

namespace App\Data\Applications;

use App\Enums\HousingCompatibilityStatus;

final readonly class HousingCompatibilityResult
{
    /**
     * @param  list<array{key: string, label: string, passed: bool, message: string}>  $checks
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public bool $compatible,
        public HousingCompatibilityStatus $status,
        public array $checks,
        public array $snapshot,
    ) {}
}
