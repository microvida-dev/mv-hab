<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;

class MaintenanceDashboardPolicy
{
    use ChecksMaintenanceAccess;

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canViewMaintenance($user);
    }
}
