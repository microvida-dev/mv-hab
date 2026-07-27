<?php

namespace App\Providers;

use App\Services\Dashboard\Timeline\TimelineAggregatorService;
use App\Services\Dashboard\Timeline\TimelineProviderRegistry;
use App\Services\Regulatory\RentLimits\PaaRentLimitProvider;
use App\Services\Regulatory\RentLimits\RentLimitProviderRegistry;
use App\Services\Regulatory\RentLimits\RsaaRentLimitProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TimelineProviderRegistry::class);
        $this->app->singleton(
            RentLimitProviderRegistry::class,
            fn ($app) => new RentLimitProviderRegistry([
                $app->make(PaaRentLimitProvider::class),
                $app->make(RsaaRentLimitProvider::class),
            ]),
        );

        $this->app->singleton(
            TimelineAggregatorService::class,
            fn ($app) => new TimelineAggregatorService(
                $app->make(TimelineProviderRegistry::class)->providers()
            ),
        );
    }

    public function boot(): void
    {
        //
    }
}
