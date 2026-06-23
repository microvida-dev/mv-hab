<?php

namespace App\Policies;

use App\Models\ListChangeLog;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ListChangeLogPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'public_lists', 'audit');
    }

    public function view(User $user, ListChangeLog $log): bool
    {
        return $this->viewAny($user);
    }
}
