<?php

namespace App\Services\Agenda\Filters;

use App\Enums\Agenda\AgendaView;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineStatus;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use Illuminate\Support\Carbon;

final readonly class AgendaFilters
{
    public function __construct(
        public AgendaView $view = AgendaView::Day,
        public ?TimelineWorkspace $workspace = null,
        public ?TimelinePriority $priority = null,
        public ?TimelineStatus $status = null,
        public ?TimelineType $type = null,
        public ?int $technicianId = null,
        public ?Carbon $from = null,
        public ?Carbon $to = null,
    ) {}
}
