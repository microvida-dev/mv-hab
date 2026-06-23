<?php

namespace App\Policies;

use App\Models\PropertyInspectionItem;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;

class PropertyInspectionItemPolicy
{
    use ChecksMaintenanceAccess;

    public function view(User $user, PropertyInspectionItem $item): bool
    {
        return $user->can('view', $item->inspection);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('inspections', 'update');
    }

    public function update(User $user, PropertyInspectionItem $item): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('inspections', 'update');
    }
}
