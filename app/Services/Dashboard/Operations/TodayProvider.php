<?php

namespace App\Services\Dashboard\Operations;

use App\Data\Dashboard\TimelineEvent;
use App\Models\User;
use App\Services\Dashboard\Timeline\Providers\AllocationTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\ApplicationTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\ComplaintTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\ContractTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\CorrectionRequestTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\DeadlineTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\DocumentTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\HearingTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\InspectionTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\InternalAlertTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\KeyHandoverTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\LotteryTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\MaintenanceTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\RentTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\RgpdTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\TenantOperationsTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\VisitTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\WorkTaskTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineAggregatorService;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;

class TodayProvider
{
    public function __construct(
        private readonly ?TimelineAggregatorService $timelineAggregator = null,
    ) {}

    /**
     * @param  array<string, mixed>  $dashboard
     * @return list<array<string, mixed>>
     */
    public function forUser(User $user, array $dashboard): array
    {
        $timeline = $this->timelineForUser($user, $dashboard);
        $items = $timeline['items'] ?? null;

        return is_array($items)
            ? array_values(array_filter($items, 'is_array'))
            : [];
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    public function timelineForUser(User $user, array $dashboard): array
    {
        return $this->aggregator()->forUser($user, $dashboard);
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return list<TimelineEvent>
     */
    public function eventsForUser(User $user, array $dashboard): array
    {
        return array_values(collect($this->providers())
            ->flatMap(fn (TimelineProviderInterface $provider): array => $provider->forUser($user, $dashboard))
            ->values()
            ->all());
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
            new WorkTaskTimelineProvider,
            new VisitTimelineProvider,
            app(InspectionTimelineProvider::class),
            new CorrectionRequestTimelineProvider,
            new HearingTimelineProvider,
            new ComplaintTimelineProvider,
            new DeadlineTimelineProvider,
            app(MaintenanceTimelineProvider::class),
            new ApplicationTimelineProvider,
            new AllocationTimelineProvider,
            new LotteryTimelineProvider,
            new RentTimelineProvider,
            new DocumentTimelineProvider,
            new ContractTimelineProvider,
            new TenantOperationsTimelineProvider,
            new KeyHandoverTimelineProvider,
            new RgpdTimelineProvider,
            new InternalAlertTimelineProvider,
        ];
    }
}
