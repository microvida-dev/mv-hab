<?php

namespace App\Policies;

use App\Models\InspectionChecklistTemplate;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;

class InspectionChecklistTemplatePolicy
{
    use ChecksMaintenanceAccess;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('inspections', 'view');
    }

    public function view(User $user, InspectionChecklistTemplate $template): bool
    {
        return $user->hasPermissionTo('inspections', 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('inspections', 'create');
    }

    public function update(User $user, InspectionChecklistTemplate $template): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('inspections', 'update');
    }
}
