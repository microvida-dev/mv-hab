<?php

namespace App\Services\Agenda\Builders;

use App\Data\Dashboard\TimelineEvent;
use App\Services\Agenda\AgendaTimelineRepository;
use App\Services\Agenda\DTO\AgendaMonth;
use App\Services\Agenda\DTO\AgendaWeek;
use App\Services\Dashboard\Timeline\TimelineMetricsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class AgendaMonthBuilder
{
    public function __construct(
        private AgendaTimelineRepository $timeline = new AgendaTimelineRepository(),
        private AgendaWeekBuilder $weekBuilder = new AgendaWeekBuilder(),
        private TimelineMetricsService $metrics = new TimelineMetricsService(),
    ) {}

    /**
     * @param  Collection<int, TimelineEvent>  $events
     */
    public function build(Carbon $date, Collection $events): AgendaMonth
    {
        $month = $date->copy()->startOfMonth();
        $start = $month->copy()->startOfWeek();
        $end = $month->copy()->endOfMonth()->endOfWeek();
        $monthEvents = $this->timeline->eventsOfMonth($events, $date);

        $weeks = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $weeks[] = $this->weekBuilder->build($cursor->copy(), $monthEvents);
            $cursor->addWeek();
        }

        return new AgendaMonth(
            month: $month,
            weeks: $weeks,
            summary: $this->metrics->calculate($monthEvents),
        );
    }
}
