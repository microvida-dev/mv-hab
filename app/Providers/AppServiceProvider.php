<?php

namespace App\Providers;

use App\Contracts\Program53\Program53FaultInjector;
use App\Contracts\Program53\Program53MetricsRecorder;
use App\Listeners\MarkMunicipalAdministratorInvitationConsumed;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineAggregatorService;
use App\Services\Dashboard\Timeline\TimelineProviderRegistry;
use App\Services\Program53\Observability\StructuredLogProgram53MetricsRecorder;
use App\Services\Program53\Resilience\NoopProgram53FaultInjector;
use App\Services\Regulatory\RentLimits\PaaRentLimitProvider;
use App\Services\Regulatory\RentLimits\RentLimitProviderRegistry;
use App\Services\Regulatory\RentLimits\RsaaRentLimitProvider;
use App\Services\Security\Program53RateLimitService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            Program53FaultInjector::class,
            NoopProgram53FaultInjector::class,
        );
        $this->app->singleton(
            Program53MetricsRecorder::class,
            StructuredLogProgram53MetricsRecorder::class,
        );
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
        VerifyEmail::toMailUsing(
            static function (object $notifiable, string $url): MailMessage {
                $recipientName = $notifiable instanceof User
                    ? $notifiable->name
                    : null;

                return (new MailMessage)
                    ->subject('Confirme o seu endereço de email — MV-HAB')
                    ->markdown('mail.auth.verify-email', [
                        'verificationUrl' => $url,
                        'recipientName' => $recipientName,
                        'expiresInMinutes' => (int) config('auth.verification.expire', 60),
                    ])
                    ->theme('mvhab');
            },
        );

        Event::listen(
            PasswordReset::class,
            MarkMunicipalAdministratorInvitationConsumed::class,
        );

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
