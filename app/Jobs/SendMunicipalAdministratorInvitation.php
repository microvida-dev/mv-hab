<?php

namespace App\Jobs;

use App\Services\Municipalities\MunicipalAdministratorInvitationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class SendMunicipalAdministratorInvitation implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $invitationId)
    {
        $this->onQueue((string) config(
            'mvhab.municipality_onboarding.invitation_queue',
            'notifications',
        ));
    }

    public function uniqueId(): string
    {
        return 'municipal-administrator-invitation:'.$this->invitationId;
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function handle(MunicipalAdministratorInvitationService $service): void
    {
        $service->send($this->invitationId);
    }

    public function failed(Throwable $exception): void
    {
        app(MunicipalAdministratorInvitationService::class)
            ->markFailed($this->invitationId, $exception);
    }
}
