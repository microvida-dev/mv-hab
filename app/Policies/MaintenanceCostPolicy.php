<?php

namespace App\Policies;

use App\Models\MaintenanceCost;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;

class MaintenanceCostPolicy
{
    use ChecksMaintenanceAccess;

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canViewMaintenance($user);
    }

    public function view(User $user, MaintenanceCost $cost): bool
    {
        return ! $user->hasRole('candidate') && $this->canViewMaintenance($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageMaintenance($user);
    }

    public function approve(User $user, MaintenanceCost $cost): bool
    {
        return $this->canApproveMaintenance($user);
    }

    public function reject(User $user, MaintenanceCost $cost): bool
    {
        return $this->canApproveMaintenance($user);
    }
}
