<?php

namespace App\Policies;

use App\Models\CommunicationLog;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;

class CommunicationLogPolicy
{
    use ChecksCommunicationAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewCommunications($user);
    }

    public function view(User $user, CommunicationLog $communication): bool
    {
        return $user->hasRole('candidate')
            ? $communication->recipient_user_id === $user->id && $this->canViewCommunications($user)
            : $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateCommunications($user);
    }

    public function update(User $user, CommunicationLog $communication): bool
    {
        return $this->canManageCommunications($user);
    }
}
