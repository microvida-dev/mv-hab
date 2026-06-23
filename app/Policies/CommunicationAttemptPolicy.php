<?php

namespace App\Policies;

use App\Models\CommunicationAttempt;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;

class CommunicationAttemptPolicy
{
    use ChecksCommunicationAccess;

    public function view(User $user, CommunicationAttempt $attempt): bool
    {
        return ! $user->hasRole('candidate') && $this->canViewCommunications($user);
    }
}
