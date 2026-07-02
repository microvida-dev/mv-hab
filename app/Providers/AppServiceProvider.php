<?php

namespace App\Providers;

use App\Services\Dashboard\Timeline\TimelineAggregatorService;
use App\Services\Dashboard\Timeline\TimelineProviderRegistry;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TimelineProviderRegistry::class);

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
