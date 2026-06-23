<?php

namespace App\Policies;

use App\Models\Contest;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ContestPolicy
{
    use ChecksPermissions;

    private const MODULE = 'contests';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, Contest $contest): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, Contest $contest): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, Contest $contest): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }

    public function publish(User $user, Contest $contest): bool
    {
        return $this->canAccess($user, self::MODULE, 'publish');
    }
}
