<?php

namespace App\Policies;

use App\Models\CommunicationDelivery;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class CommunicationDeliveryPolicy
{
    use ChecksCommunicationAccess;

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function view(User $user, CommunicationDelivery $delivery): bool
    {
        return ! $user->hasRole('candidate') && $this->canViewCommunications($user);
    }

    public function update(User $user, CommunicationDelivery $delivery): bool
    {
        return $this->canManageCommunications($user);
    }

    public function resendBackoffice(
        User $user,
        CommunicationDelivery $delivery,
    ): bool {
        return $user->hasPermission(
            'communications.deliveries.resend',
        ) && $this->municipalScope->ownsCommunicationDelivery(
            $user,
            $delivery,
        );
    }

    public function registerPostalBackoffice(
        User $user,
        CommunicationDelivery $delivery,
    ): bool {
        return $user->hasPermission(
            'communications.deliveries.postal',
        ) && $this->municipalScope->ownsCommunicationDelivery(
            $user,
            $delivery,
        );
    }
}
