<?php

namespace App\Policies;

use App\Models\AccessLog;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class AccessLogPolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return $this->audit($user);
    }

    public function view(User $user, AccessLog $log): bool
    {
        return $this->audit($user);
    }
}
