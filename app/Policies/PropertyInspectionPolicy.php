<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\PropertyInspection;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;

class PropertyInspectionPolicy
{
    use ChecksMaintenanceAccess;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('candidate') ? $user->hasPermissionTo('inspections', 'view') : $user->hasPermissionTo('inspections', 'view');
    }

    public function view(User $user, PropertyInspection $inspection): bool
    {
        if ($user->hasRole('candidate')) {
            return $inspection->tenant_visible && $this->ownsContract($user, $this->contract($inspection));
        }

        return $user->hasPermissionTo('inspections', 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('inspections', 'create');
    }

    public function update(User $user, PropertyInspection $inspection): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('inspections', 'update');
    }

    public function approve(User $user, PropertyInspection $inspection): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('inspections', 'approve');
    }

    private function contract(PropertyInspection $inspection): ?Contract
    {
        $contract = $inspection->leaseContract;

        return $contract instanceof Contract ? $contract : null;
    }
}
