<?php

namespace App\Services\Dashboard\Timeline;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
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

        if ($event->priority === TimelinePriority::Critical) {
            return 10;
        }

        return match ($event->type) {
            TimelineType::CorrectionRequest,
            TimelineType::CorrectionResponse,
            TimelineType::Hearing,
            TimelineType::HearingSubmission,
            TimelineType::Complaint,
            TimelineType::ComplaintAdditionalInformation,
            TimelineType::ComplaintDecision => 20,

            TimelineType::Task => 30,

            TimelineType::Inspection,
            TimelineType::Visit => 40,

            TimelineType::Deadline => 50,
        };
    }
}
