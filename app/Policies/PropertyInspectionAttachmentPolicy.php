<?php

namespace App\Policies;

use App\Models\PropertyInspectionAttachment;
use App\Models\User;
use App\Policies\Concerns\ChecksMaintenanceAccess;

class PropertyInspectionAttachmentPolicy
{
    use ChecksMaintenanceAccess;

    public function view(User $user, PropertyInspectionAttachment $attachment): bool
    {
        if ($user->hasRole('candidate')) {
            return $attachment->visible_to_tenant && $user->can('view', $attachment->inspection);
        }

        return $user->hasPermissionTo('inspections', 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('inspections', 'update');
    }

    public function download(User $user, PropertyInspectionAttachment $attachment): bool
    {
        return $this->view($user, $attachment);
    }
}
