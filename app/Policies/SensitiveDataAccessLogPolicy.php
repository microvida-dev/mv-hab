<?php

namespace App\Policies;

use App\Models\SensitiveDataAccessLog;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class SensitiveDataAccessLogPolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return $this->audit($user);
    }

    public function view(User $user, SensitiveDataAccessLog $log): bool
    {
        return $this->audit($user);
    }
}
