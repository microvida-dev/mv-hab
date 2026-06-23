<?php

namespace App\Policies;

use App\Models\MaintenanceAssignment;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;

class MaintenanceAssignmentPolicy
{
    use ChecksMaintenanceAccess;

    public function view(User $user, MaintenanceAssignment $assignment): bool
    {
        return $this->canViewMaintenance($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageMaintenance($user);
    }

    public function update(User $user, MaintenanceAssignment $assignment): bool
    {
        return $this->canManageMaintenance($user);
    }
}
