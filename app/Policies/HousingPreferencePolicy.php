<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\HousingPreference;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class HousingPreferencePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, HousingPreference $preference): bool
    {
        return $user->hasRole('candidate')
            ? $preference->user_id === $user->id && $this->canAccess($user, 'allocations', 'view')
            : $this->viewAny($user);
    }

    public function update(User $user, Application $application): bool
    {
        return $user->hasRole('candidate')
            && $application->user_id === $user->id
            && $application->allocations()->doesntExist()
            && $this->canAccess($user, 'allocations', 'update');
    }
}
