<?php

namespace App\Policies\Concerns;

use App\Models\Contract;
use App\Models\User;

trait ChecksMaintenanceAccess
{
    use ChecksPermissions;

    protected function canViewMaintenance(User $user): bool
    {
        return $this->canAccess($user, 'maintenance_requests', 'view') || $user->hasPermissionTo('inspections', 'view');
    }

    protected function canCreateMaintenance(User $user): bool
    {
        return ! $user->hasRole('auditor') && $this->canAccess($user, 'maintenance_requests', 'create');
    }

    protected function canManageMaintenance(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'maintenance_requests', 'update');
    }

    protected function canApproveMaintenance(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'maintenance_requests', 'approve');
    }

    protected function ownsContract(User $user, ?Contract $contract): bool
    {
        return $contract !== null && $contract->user_id === $user->id;
    }
}
