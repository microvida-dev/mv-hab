<?php

declare(strict_types=1);

namespace App\Services\Dashboard\Timeline;

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

final class TimelineProviderRegistry
{
    /**
     * @return array<int, TimelineProviderInterface>
     */
    public function providers(): array
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
