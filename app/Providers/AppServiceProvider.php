<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Dashboard\Timeline\Providers\ComplaintTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\CorrectionRequestTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\DeadlineTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\HearingTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\InspectionTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\VisitTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\WorkTaskTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineAggregatorService;
use App\Services\Dashboard\Timeline\Providers\MaintenanceTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\ApplicationTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\KeyHandoverTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\RgpdTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\InternalAlertTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\AllocationTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\LotteryTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\RentTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\DocumentTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\ContractTimelineProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TimelineAggregatorService::class, fn () => new TimelineAggregatorService([
            new WorkTaskTimelineProvider(),
            new VisitTimelineProvider(),
            new InspectionTimelineProvider(),
            new CorrectionRequestTimelineProvider(),
            new HearingTimelineProvider(),
            new ComplaintTimelineProvider(),
            new DeadlineTimelineProvider(),
            new MaintenanceTimelineProvider(),
            new ApplicationTimelineProvider(),
            new AllocationTimelineProvider(),
            new KeyHandoverTimelineProvider(),
            new RgpdTimelineProvider(),
            new InternalAlertTimelineProvider(),
            new LotteryTimelineProvider(),
            new RentTimelineProvider(),
            new DocumentTimelineProvider(),
            new ContractTimelineProvider(),
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
