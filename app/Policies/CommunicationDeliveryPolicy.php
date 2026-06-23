<?php

namespace App\Policies;

use App\Models\CommunicationDelivery;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;

class CommunicationDeliveryPolicy
{
    use ChecksCommunicationAccess;

    public function view(User $user, CommunicationDelivery $delivery): bool
    {
        return ! $user->hasRole('candidate') && $this->canViewCommunications($user);
    }

    public function update(User $user, CommunicationDelivery $delivery): bool
    {
        return $this->canManageCommunications($user);
    }
}
