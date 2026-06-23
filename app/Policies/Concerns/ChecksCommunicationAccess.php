<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksCommunicationAccess
{
    protected function canViewCommunications(User $user): bool
    {
        return $user->hasPermissionTo('notifications', 'view');
    }

    protected function canCreateCommunications(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('notifications', 'create');
    }

    protected function canManageCommunications(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('notifications', 'update');
    }

    protected function canPublishCommunications(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('notifications', 'publish');
    }
}
