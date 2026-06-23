<?php

namespace App\Policies;

use App\Models\HousingApplication;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class HousingApplicationPolicy
{
    use ChecksPermissions;

    private const MODULE = 'applications';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, HousingApplication $housingApplication): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, HousingApplication $housingApplication): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, HousingApplication $housingApplication): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }

    public function approve(User $user, HousingApplication $housingApplication): bool
    {
        return $this->canAccess($user, self::MODULE, 'approve');
    }

    public function reject(User $user, HousingApplication $housingApplication): bool
    {
        return $this->canAccess($user, self::MODULE, 'reject');
    }
}
