<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class UserPolicy
{
    use ChecksPermissions;

    private const MODULE = 'users';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, User $model): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, User $model): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, User $model): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }
}
