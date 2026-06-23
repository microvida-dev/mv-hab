<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisitAvailability;
use App\Policies\Concerns\ChecksPermissions;

class VisitAvailabilityPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'visits', 'view');
    }

    public function view(User $user, VisitAvailability $availability): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'visits', 'create');
    }

    public function update(User $user, VisitAvailability $availability): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'visits', 'update');
    }

    public function delete(User $user, VisitAvailability $availability): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'visits', 'delete');
    }
}
