<?php

namespace App\Providers;

use App\Services\Dashboard\Timeline\TimelineAggregatorService;
use App\Services\Dashboard\Timeline\TimelineProviderRegistry;
use App\Services\Regulatory\RentLimits\PaaRentLimitProvider;
use App\Services\Regulatory\RentLimits\RentLimitProviderRegistry;
use App\Services\Regulatory\RentLimits\RsaaRentLimitProvider;
use App\Services\Security\Program53RateLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $limiters = [
            'program53.export-preview' => Program53RateLimitService::EXPORT_PREVIEW,
            'program53.export-request' => Program53RateLimitService::EXPORT_REQUEST,
            'program53.export-download' => Program53RateLimitService::EXPORT_DOWNLOAD,
            'program53.batch-seal' => Program53RateLimitService::BATCH_SEAL,
            'program53.batch-publish' => Program53RateLimitService::BATCH_PUBLISH,
            'program53.revalidation-seal' => Program53RateLimitService::REVALIDATION_SEAL,
        ];

        foreach ($limiters as $name => $operation) {
            RateLimiter::for(
                $name,
                fn (Request $request): array => app(Program53RateLimitService::class)
                    ->limits($request, $operation),
            );
        }
    }
}
