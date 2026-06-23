<?php

namespace App\Policies;

use App\Models\HousingVisit;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class HousingVisitPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'visits', 'view');
    }

    public function view(User $user, HousingVisit $visit): bool
    {
        return $user->hasRole('candidate')
            ? $visit->belongsToCandidate($user) && $this->canAccess($user, 'visits', 'view')
            : $this->canAccess($user, 'visits', 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate') && $this->canAccess($user, 'visits', 'create');
    }

    public function update(User $user, HousingVisit $visit): bool
    {
        return $user->hasRole('candidate')
            ? $visit->belongsToCandidate($user) && $visit->isActive() && $this->canAccess($user, 'visits', 'update')
            : $this->canAccess($user, 'visits', 'update');
    }

    public function cancel(User $user, HousingVisit $visit): bool
    {
        return $this->update($user, $visit);
    }

    public function approve(User $user, HousingVisit $visit): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'visits', 'approve');
    }

    public function reject(User $user, HousingVisit $visit): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'visits', 'reject');
    }
}
