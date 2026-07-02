<?php

namespace App\Services\Agenda;

use App\Enums\Agenda\AgendaView;
use App\Models\User;
use App\Services\Agenda\Builders\AgendaDayBuilder;
use App\Services\Agenda\Builders\AgendaMonthBuilder;
use App\Services\Agenda\Builders\AgendaWeekBuilder;
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
     * @return array<string, mixed>
     */
    public function build(User $user, AgendaFilters $filters, array $dashboard = []): array
    {
        $date = $filters->from ?? now();

        $timeline = $this->timeline->forUser($user, $dashboard);
        $events = $this->timeline->eventsForUser($user, $dashboard);
        $events = $this->eventFilter->apply($events, $filters);

        $agenda = match ($filters->view) {
            AgendaView::Day => $this->dayBuilder->build($date, $events),
            AgendaView::Week => $this->weekBuilder->build($date, $events),
            AgendaView::Month => $this->monthBuilder->build($date, $events),
        };

        return array_merge($agenda->toArray(), [
            'nextAction' => $timeline['nextAction'] ?? null,
        ]);
    }
}
