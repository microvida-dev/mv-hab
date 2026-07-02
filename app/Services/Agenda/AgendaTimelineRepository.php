<?php

namespace App\Services\Agenda;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class AgendaTimelineRepository
{
    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return Collection<int, TimelineEvent>
     */
    public function eventsOfDay(Collection $events, Carbon $date): Collection
    {
        return $this->sort(
            $events->filter(fn (TimelineEvent $event): bool => $event->datetime?->isSameDay($date) ?? false)
        );
    }

    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return Collection<int, TimelineEvent>
     */
    public function eventsOfWeek(Collection $events, Carbon $date): Collection
    {
        return $this->eventsBetween(
            $events,
            $date->copy()->startOfWeek(),
            $date->copy()->endOfWeek(),
        );
    }

    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return Collection<int, TimelineEvent>
     */
    public function eventsOfMonth(Collection $events, Carbon $date): Collection
    {
        return $this->eventsBetween(
            $events,
            $date->copy()->startOfMonth(),
            $date->copy()->endOfMonth(),
        );
    }

    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return Collection<int, TimelineEvent>
     */
    public function eventsBetween(Collection $events, Carbon $from, Carbon $to): Collection
    {
        return $this->sort(
            $events->filter(fn (TimelineEvent $event): bool => $event->datetime?->betweenIncluded($from, $to) ?? false)
        );
    }

    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return Collection<int, TimelineEvent>
     */
    public function eventsByWorkspace(Collection $events, TimelineWorkspace $workspace): Collection
    {
        return $this->sort(
            $events->filter(fn (TimelineEvent $event): bool => $event->workspace === $workspace)
        );
    }

    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return Collection<int, TimelineEvent>
     */
    public function eventsByTechnician(Collection $events, int $technicianId): Collection
    {
        return $this->sort(
            $events->filter(
                fn (TimelineEvent $event): bool => (int) ($event->metadata['assigned_to'] ?? $event->metadata['technician_id'] ?? 0) === $technicianId
            )
        );
    }

    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return Collection<int, TimelineEvent>
     */
    public function sort(Collection $events): Collection
    {
        return $events
            ->sortBy([
                fn (TimelineEvent $event): int => $event->priorityWeight(),
                fn (TimelineEvent $event): string => $event->datetime?->toIso8601String() ?? '9999-12-31T23:59:59',
                fn (TimelineEvent $event): string => $event->workspace?->value ?? '',
                fn (TimelineEvent $event): string => $event->type->value,
            ])
            ->values();
    }
}
