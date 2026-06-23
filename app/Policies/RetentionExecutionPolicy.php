<?php

namespace App\Policies;

use App\Models\RetentionExecution;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class RetentionExecutionPolicy
{
    use HandlesSecurityAccess;

    public function view(User $user, RetentionExecution $execution): bool
    {
        return $this->privacy($user);
    }

    public function run(User $user, RetentionExecution $execution): bool
    {
        return $this->privacy($user, 'approve');
    }
}
