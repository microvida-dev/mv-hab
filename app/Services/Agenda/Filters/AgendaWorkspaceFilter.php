<?php

namespace App\Services\Agenda\Filters;

use App\Enums\Dashboard\Timeline\TimelineWorkspace;

final readonly class AgendaWorkspaceFilter
{
    public function __construct(
        public TimelineWorkspace $workspace,
    ) {}
}
