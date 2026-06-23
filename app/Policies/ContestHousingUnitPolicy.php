<?php

namespace App\Policies;

use App\Models\ContestHousingUnit;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ContestHousingUnitPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, ContestHousingUnit $unit): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'create');
    }

    public function update(User $user, ContestHousingUnit $unit): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }

    public function delete(User $user, ContestHousingUnit $unit): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }
}
