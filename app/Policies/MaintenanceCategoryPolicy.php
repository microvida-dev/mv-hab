<?php

namespace App\Policies;

use App\Models\MaintenanceCategory;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;

class MaintenanceCategoryPolicy
{
    use ChecksMaintenanceAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewMaintenance($user);
    }

    public function view(User $user, MaintenanceCategory $category): bool
    {
        return $this->canViewMaintenance($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageMaintenance($user);
    }

    public function update(User $user, MaintenanceCategory $category): bool
    {
        return $this->canManageMaintenance($user);
    }

    public function delete(User $user, MaintenanceCategory $category): bool
    {
        return $this->canApproveMaintenance($user);
    }
}
