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
            ->sort(fn (TimelineEvent $left, TimelineEvent $right): int => $this->compareEvents($left, $right))
            ->values();
    }

    private function compareEvents(TimelineEvent $left, TimelineEvent $right): int
    {
        $comparisons = [
            $this->eventDate($left) <=> $this->eventDate($right),
            $left->priorityWeight() <=> $right->priorityWeight(),
            $this->workspace($left) <=> $this->workspace($right),
            $left->type->value <=> $right->type->value,
            strcasecmp($left->title, $right->title),
        ];

        foreach ($comparisons as $comparison) {
            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    private function eventDate(TimelineEvent $event): string
    {
        return $event->datetime?->toIso8601String() ?? '9999-12-31T23:59:59';
    }

    private function workspace(TimelineEvent $event): string
    {
        if ($event->workspace === null) {
            return '';
        }

        return $event->workspace->value;
    }
}
