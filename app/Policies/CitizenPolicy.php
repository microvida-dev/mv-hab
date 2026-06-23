<?php

namespace App\Policies;

use App\Models\Citizen;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class CitizenPolicy
{
    use ChecksPermissions;

    private const MODULE = 'citizens';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, Citizen $citizen): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, Citizen $citizen): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, Citizen $citizen): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }
}
