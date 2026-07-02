<?php

namespace App\Services\Dashboard\Timeline;

use App\Data\Dashboard\TimelineEvent;
use Illuminate\Support\Collection;

class TimelineMetricsService
{
    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return array<string, mixed>
     */
    public function calculate(Collection $events): array
    {
        return [
            'total' => $events->count(),
            'critical' => $events->where('priority', 'critical')->count(),
            'high' => $events->where('priority', 'high')->count(),
            'overdue' => $events->filter(fn (TimelineEvent $event): bool => $event->datetime?->isPast() ?? false)->count(),
            'today' => $events->filter(fn (TimelineEvent $event): bool => $event->datetime?->isToday() ?? false)->count(),
            'by_workspace' => $this->byWorkspace($events),
            'by_type' => $this->byType($events),
        ];
    }

    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return array<string, int>
     */
    private function byWorkspace(Collection $events): array
    {
        return $events
            ->groupBy(fn (TimelineEvent $event): string => $event->workspace ?? 'unknown')
            ->map(fn (Collection $items): int => $items->count())
            ->sortDesc()
            ->all();
    }

    /**
     * @param  Collection<int, TimelineEvent>  $events
     * @return array<string, int>
     */
    private function byType(Collection $events): array
    {
        return $events
            ->groupBy(fn (TimelineEvent $event): string => $event->type)
            ->map(fn (Collection $items): int => $items->count())
            ->sortDesc()
            ->all();
    }
}
