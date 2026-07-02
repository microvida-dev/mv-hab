<?php

namespace App\Services\Agenda\Builders;

use App\Data\Dashboard\TimelineEvent;
use App\Services\Agenda\DTO\AgendaDay;
use App\Services\Dashboard\Timeline\TimelineMetricsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class AgendaDayBuilder
{
    public function __construct(
        private TimelineMetricsService $metrics = new TimelineMetricsService(),
    ) {}

    /**
     * @param  Collection<int, TimelineEvent>  $events
     */
    public function build(Carbon $date, Collection $events): AgendaDay
    {
        $dayEvents = $events
            ->filter(fn (TimelineEvent $event): bool => $event->datetime?->isSameDay($date) ?? false)
            ->sortBy([
                fn (TimelineEvent $event): int => $event->priorityWeight(),
                fn (TimelineEvent $event): string => $event->datetime?->toIso8601String() ?? '9999-12-31T23:59:59',
            ])
            ->values();

        return new AgendaDay(
            date: $date->copy(),
            events: $dayEvents->all(),
            statistics: $this->metrics->calculate($dayEvents),
        );
    }
}
