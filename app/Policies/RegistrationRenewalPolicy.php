<?php

namespace App\Policies;

use App\Models\RegistrationRenewal;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class RegistrationRenewalPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('candidate')
            && $this->canAccess($user, 'simulator', 'view');
    }

    public function view(User $user, RegistrationRenewal $registrationRenewal): bool
    {
        return $registrationRenewal->user_id === $user->id
            && $this->canAccess($user, 'simulator', 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate')
            && $this->canAccess($user, 'simulator', 'create');
    }

    public function update(User $user, RegistrationRenewal $registrationRenewal): bool
    {
        return $registrationRenewal->user_id === $user->id
            && $this->canAccess($user, 'simulator', 'update');
    }
}
