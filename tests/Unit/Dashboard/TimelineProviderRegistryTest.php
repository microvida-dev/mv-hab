<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

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
use App\Services\Dashboard\Timeline\TimelineProviderInterface;
use App\Services\Dashboard\Timeline\TimelineProviderRegistry;
use Tests\TestCase;

final class TimelineProviderRegistryTest extends TestCase
{
    public function test_it_registers_expected_timeline_providers_once(): void
    {
        $providers = (new TimelineProviderRegistry())->providers();

        $classes = array_map(
            static fn (TimelineProviderInterface $provider): string => $provider::class,
            $providers,
        );

        $this->assertSameSize(array_unique($classes), $classes);

        $this->assertSame([
            WorkTaskTimelineProvider::class,
            VisitTimelineProvider::class,
            InspectionTimelineProvider::class,
            CorrectionRequestTimelineProvider::class,
            HearingTimelineProvider::class,
            ComplaintTimelineProvider::class,
            DeadlineTimelineProvider::class,
            MaintenanceTimelineProvider::class,
            ApplicationTimelineProvider::class,
            AllocationTimelineProvider::class,
            LotteryTimelineProvider::class,
            RentTimelineProvider::class,
            DocumentTimelineProvider::class,
            ContractTimelineProvider::class,
            TenantOperationsTimelineProvider::class,
            KeyHandoverTimelineProvider::class,
            RgpdTimelineProvider::class,
            InternalAlertTimelineProvider::class,
        ], $classes);
    }
}
