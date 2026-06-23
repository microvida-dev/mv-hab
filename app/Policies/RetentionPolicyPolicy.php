<?php

namespace App\Policies;

use App\Models\RetentionPolicy;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class RetentionPolicyPolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return $this->privacy($user);
    }

    public function view(User $user, RetentionPolicy $policy): bool
    {
        return $this->privacy($user);
    }

    public function create(User $user): bool
    {
        return $this->privacy($user, 'create');
    }

    public function update(User $user, RetentionPolicy $policy): bool
    {
        return $this->privacy($user, 'update');
    }
}
