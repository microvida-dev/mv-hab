<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksPermissions
{
    protected function canAccess(User $user, string $module, string $action): bool
    {
        return $user->hasPermissionTo($module, $action);
    }
}
