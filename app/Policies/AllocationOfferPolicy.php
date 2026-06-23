<?php

namespace App\Policies;

use App\Models\AllocationOffer;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class AllocationOfferPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, AllocationOffer $offer): bool
    {
        return $user->hasRole('candidate')
            ? $offer->user_id === $user->id && $this->canAccess($user, 'allocations', 'view')
            : $this->viewAny($user);
    }

    public function update(User $user, AllocationOffer $offer): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }

    public function respond(User $user, AllocationOffer $offer): bool
    {
        return $user->hasRole('candidate') && $offer->user_id === $user->id && $this->canAccess($user, 'allocations', 'update');
    }
}
