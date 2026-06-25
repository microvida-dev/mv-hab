<?php

namespace App\Policies;

use App\Models\RgpdApproval;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class DpoApprovalPolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return $this->privacy($user) || $this->audit($user);
    }

    public function view(User $user, RgpdApproval $approval): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->privacy($user, 'create');
    }

    public function approve(User $user): bool
    {
        return $this->privacy($user, 'approve');
    }
}
