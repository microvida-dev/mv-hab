<?php

namespace App\Policies;

use App\Models\Allocation;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class AllocationPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, Allocation $allocation): bool
    {
        return $user->hasRole('candidate')
            ? $allocation->user_id === $user->id && $this->canAccess($user, 'allocations', 'view')
            : $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'create');
    }

    public function update(User $user, Allocation $allocation): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }

    public function withdraw(User $user, Allocation $allocation): bool
    {
        return $user->hasRole('candidate') && $allocation->user_id === $user->id && $this->canAccess($user, 'allocations', 'update');
    }
}
