<?php

namespace App\Policies;

use App\Models\ConsentPurpose;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class ConsentPurposePolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ConsentPurpose $purpose): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->privacy($user, 'create');
    }

    public function update(User $user, ConsentPurpose $purpose): bool
    {
        return $this->privacy($user, 'update');
    }
}
