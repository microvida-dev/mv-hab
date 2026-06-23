<?php

namespace App\Policies;

use App\Models\MaintenanceSupplier;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;

class MaintenanceSupplierPolicy
{
    use ChecksMaintenanceAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewMaintenance($user);
    }

    public function view(User $user, MaintenanceSupplier $supplier): bool
    {
        return $this->canViewMaintenance($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageMaintenance($user);
    }

    public function update(User $user, MaintenanceSupplier $supplier): bool
    {
        return $this->canManageMaintenance($user);
    }
}
