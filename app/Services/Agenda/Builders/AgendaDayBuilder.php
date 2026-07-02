<?php

namespace App\Services\Agenda\Builders;

use App\Data\Dashboard\TimelineEvent;
use App\Services\Agenda\AgendaTimelineRepository;
use App\Services\Agenda\DTO\AgendaDay;
use App\Services\Dashboard\Timeline\TimelineMetricsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class AgendaDayBuilder
{
    public function __construct(
        private AgendaTimelineRepository $timeline = new AgendaTimelineRepository(),
        private TimelineMetricsService $metrics = new TimelineMetricsService(),
    ) {}

    /**
     * @param  Collection<int, TimelineEvent>  $events
     */
    public function build(Carbon $date, Collection $events): AgendaDay
    {
        $dayEvents = $this->timeline->eventsOfDay($events, $date);

        return new AgendaDay(
            date: $date->copy(),
            events: $dayEvents->all(),
            statistics: $this->metrics->calculate($dayEvents),
        );
    }
}
