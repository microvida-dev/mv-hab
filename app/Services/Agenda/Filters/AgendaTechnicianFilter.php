<?php

namespace App\Services\Agenda\Filters;

final readonly class AgendaTechnicianFilter
{
    public function __construct(
        public ?int $technicianId = null,
        public bool $onlyMine = false,
    ) {}
}
