<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserConsent;
use App\Policies\Concerns\HandlesSecurityAccess;

class UserConsentPolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('candidate') || $this->privacy($user);
    }

    public function view(User $user, UserConsent $consent): bool
    {
        return $consent->user_id === $user->id || $this->privacy($user);
    }

    public function withdraw(User $user, UserConsent $consent): bool
    {
        return $consent->user_id === $user->id;
    }
}
