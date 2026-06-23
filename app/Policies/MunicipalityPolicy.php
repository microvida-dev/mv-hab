<?php

namespace App\Policies;

use App\Models\Municipality;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class MunicipalityPolicy
{
    use ChecksPermissions;

    private const MODULE = 'municipalities';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, Municipality $municipality): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, Municipality $municipality): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, Municipality $municipality): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }
}
