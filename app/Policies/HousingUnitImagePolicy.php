<?php

namespace App\Policies;

use App\Models\HousingUnitImage;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class HousingUnitImagePolicy
{
    use ChecksPermissions;

    private const MODULE = 'housing_units';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, HousingUnitImage $housingUnitImage): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function update(User $user, HousingUnitImage $housingUnitImage): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, HousingUnitImage $housingUnitImage): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete') || $this->canAccess($user, self::MODULE, 'update');
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update');
    }

    public function updateBackoffice(User $user, HousingUnitImage $housingUnitImage): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update');
    }

    public function deleteBackoffice(User $user, HousingUnitImage $housingUnitImage): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update');
    }
}
