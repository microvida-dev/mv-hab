<?php

namespace App\Services\Dashboard\Timeline;

use App\Data\Dashboard\TimelineEvent;
use Illuminate\Support\Collection;

class NextActionResolver
{
    /**
     * @param  Collection<int, TimelineEvent>  $events
     */
    public function resolve(Collection $events): ?TimelineEvent
    {
        return $events
            ->sortBy([
                fn (TimelineEvent $event): int => $this->businessWeight($event),
                fn (TimelineEvent $event): int => $event->priorityWeight(),
                fn (TimelineEvent $event): string => $event->datetime?->toIso8601String() ?? '9999-12-31T23:59:59',
            ])
            ->first();
    }

    private function businessWeight(TimelineEvent $event): int
    {
        if ($event->datetime?->isPast()) {
            return 1;
        }

        if ($event->priority === 'critical') {
            return 10;
        }

        return match ($event->type) {
            'correction-request',
            'correction-response',
            'hearing',
            'hearing-submission',
            'complaint',
            'complaint-additional-information',
            'complaint-decision' => 20,

            'task' => 30,

            'inspection',
            'visit' => 40,

            'deadline' => 50,

            default => 80,
        };
    }
}
