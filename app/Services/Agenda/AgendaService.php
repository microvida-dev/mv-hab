<?php

namespace App\Services\Agenda;

use App\Enums\Agenda\AgendaView;
use App\Models\User;
use App\Services\Agenda\Builders\AgendaDayBuilder;
use App\Services\Agenda\Builders\AgendaMonthBuilder;
use App\Services\Agenda\Builders\AgendaWeekBuilder;
use App\Services\Agenda\DTO\AgendaDay;
use App\Services\Agenda\DTO\AgendaMonth;
use App\Services\Agenda\DTO\AgendaWeek;
use App\Services\Agenda\Filters\AgendaFilters;
use App\Services\Dashboard\Timeline\TimelineAggregatorService;

final readonly class AgendaService
{
    public function __construct(
        private TimelineAggregatorService $timeline,
        private AgendaEventFilter $eventFilter,
        private AgendaDayBuilder $dayBuilder,
        private AgendaWeekBuilder $weekBuilder,
        private AgendaMonthBuilder $monthBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $dashboard
     */
    public function build(User $user, AgendaFilters $filters, array $dashboard = []): AgendaDay|AgendaWeek|AgendaMonth
    {
        $date = $filters->from ?? now();

        $events = $this->timeline->eventsForUser($user, $dashboard);
        $events = $this->eventFilter->apply($events, $filters);

        return match ($filters->view) {
            AgendaView::Day => $this->dayBuilder->build($date, $events),
            AgendaView::Week => $this->weekBuilder->build($date, $events),
            AgendaView::Month => $this->monthBuilder->build($date, $events),
        };
    }
}
