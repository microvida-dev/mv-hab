<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Municipalities\MunicipalAdministratorInvitationService;
use Illuminate\Auth\Events\PasswordReset;

final class MarkMunicipalAdministratorInvitationConsumed
{
    public function __construct(
        private readonly MunicipalAdministratorInvitationService $invitations,
    ) {}

    public function handle(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            $this->invitations->markConsumed($event->user);
        }
    }
}
