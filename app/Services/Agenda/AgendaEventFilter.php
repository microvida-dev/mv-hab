<?php

namespace App\Services\Agenda;

use App\Data\Dashboard\TimelineEvent;
use App\Services\Agenda\Filters\AgendaFilters;
use Illuminate\Support\Collection;

final class AgendaEventFilter
{
    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return Collection<int, TimelineEvent>
     */
    public function apply(Collection $events, AgendaFilters $filters): Collection
    {
        return $events
            ->when(
                $filters->workspace,
                fn (Collection $items) => $items->filter(
                    fn (TimelineEvent $event) => $event->workspace === $filters->workspace
                )
            )
            ->when(
                $filters->priority,
                fn (Collection $items) => $items->filter(
                    fn (TimelineEvent $event) => $event->priority === $filters->priority
                )
            )
            ->when(
                $filters->status,
                fn (Collection $items) => $items->filter(
                    fn (TimelineEvent $event) => $event->status === $filters->status
                )
            )
            ->when(
                $filters->type,
                fn (Collection $items) => $items->filter(
                    fn (TimelineEvent $event) => $event->type === $filters->type
                )
            )
            ->when(
                $filters->from,
                fn (Collection $items) => $items->filter(
                    fn (TimelineEvent $event) => $event->datetime?->gte($filters->from) ?? false
                )
            )
            ->when(
                $filters->to,
                fn (Collection $items) => $items->filter(
                    fn (TimelineEvent $event) => $event->datetime?->lte($filters->to) ?? false
                )
            )
            ->when(
                $filters->technicianId,
                fn (Collection $items) => $items->filter(
                    fn (TimelineEvent $event) => (int) ($event->metadata['assigned_to'] ?? $event->metadata['technician_id'] ?? 0) === $filters->technicianId
                )
            )
            ->values();
    }
}
