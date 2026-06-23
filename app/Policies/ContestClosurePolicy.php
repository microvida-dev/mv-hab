<?php

namespace App\Policies;

use App\Models\ContestClosure;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ContestClosurePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'contests', 'view');
    }

    public function view(User $user, ContestClosure $closure): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contests', 'approve');
    }
}
