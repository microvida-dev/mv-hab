<?php

namespace App\Services\Agenda\Builders;

use App\Data\Dashboard\TimelineEvent;
use App\Services\Agenda\AgendaTimelineRepository;
use App\Services\Agenda\DTO\AgendaDay;
use App\Services\Agenda\DTO\AgendaWeek;
use App\Services\Dashboard\Timeline\TimelineMetricsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class AgendaWeekBuilder
{
    public function __construct(
        private AgendaTimelineRepository $timeline = new AgendaTimelineRepository(),
        private AgendaDayBuilder $dayBuilder = new AgendaDayBuilder(),
        private TimelineMetricsService $metrics = new TimelineMetricsService(),
    ) {}

    /**
     * @param  Collection<int, TimelineEvent>  $events
     */
    public function build(Carbon $date, Collection $events): AgendaWeek
    {
        $start = $date->copy()->startOfWeek();
        $end = $date->copy()->endOfWeek();
        $weekEvents = $this->timeline->eventsOfWeek($events, $date);

        $days = collect(range(0, 6))
            ->map(fn (int $offset): AgendaDay => $this->dayBuilder->build($start->copy()->addDays($offset), $weekEvents))
            ->all();

        return new AgendaWeek(
            start: $start,
            end: $end,
            days: $days,
            summary: $this->metrics->calculate($weekEvents),
        );
    }
}
