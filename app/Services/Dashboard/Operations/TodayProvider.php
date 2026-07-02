<?php

namespace App\Services\Dashboard\Operations;

use App\Data\Dashboard\TimelineEvent;
use App\Models\User;
use App\Services\Dashboard\Timeline\Providers\ComplaintTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\CorrectionRequestTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\DeadlineTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\HearingTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\InspectionTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\VisitTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\WorkTaskTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineAggregatorService;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;

class TodayProvider
{
    public function __construct(
        private readonly ?TimelineAggregatorService $timelineAggregator = null,
    ) {}

    public function forUser(User $user, array $dashboard): array
    {
        $timeline = $this->timelineForUser($user, $dashboard);

        return $timeline['items'] ?? [];
    }

    public function timelineForUser(User $user, array $dashboard): array
    {
        return $this->aggregator()->forUser($user, $dashboard);
    }

    /**
     * @return array<int, TimelineEvent>
     */
    public function eventsForUser(User $user, array $dashboard): array
    {
        return collect($this->providers())
            ->flatMap(fn (TimelineProviderInterface $provider): array => $provider->forUser($user, $dashboard))
            ->values()
            ->all();
    }

    private function aggregator(): TimelineAggregatorService
    {
        return $this->timelineAggregator ?? new TimelineAggregatorService($this->providers());
    }

    /**
     * @return array<int, TimelineProviderInterface>
     */
    private function providers(): array
    {
        return [
            new WorkTaskTimelineProvider(),
            new VisitTimelineProvider(),
            new InspectionTimelineProvider(),
            new CorrectionRequestTimelineProvider(),
            new HearingTimelineProvider(),
            new ComplaintTimelineProvider(),
            new DeadlineTimelineProvider(),
        ];
    }
}
