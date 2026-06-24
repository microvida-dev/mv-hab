<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class WorkTaskDashboardPolicy
{
    use ChecksPermissions;

    public function view(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && ($this->canAccess($user, 'work_tasks', 'dashboard') || $this->canAccess($user, 'work_tasks', 'view'));
    }
}
