<?php

namespace App\Policies;

use App\Models\DrawAttendance;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class DrawAttendancePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, DrawAttendance $attendance): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }
}
