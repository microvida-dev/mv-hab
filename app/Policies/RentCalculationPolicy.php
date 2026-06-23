<?php

namespace App\Policies;

use App\Models\RentCalculation;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class RentCalculationPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'contracts', 'view');
    }

    public function view(User $user, RentCalculation $rentCalculation): bool
    {
        return $user->hasRole('candidate')
            ? $rentCalculation->user_id === $user->id && $this->canAccess($user, 'contracts', 'view')
            : $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'create');
    }

    public function update(User $user, RentCalculation $rentCalculation): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'update');
    }

    public function approve(User $user, RentCalculation $rentCalculation): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'approve');
    }
}
