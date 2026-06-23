<?php

namespace App\Policies;

use App\Models\HousingUnitPublicDocument;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class HousingUnitPublicDocumentPolicy
{
    use ChecksPermissions;

    private const MODULE = 'housing_units';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, HousingUnitPublicDocument $housingUnitPublicDocument): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function update(User $user, HousingUnitPublicDocument $housingUnitPublicDocument): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, HousingUnitPublicDocument $housingUnitPublicDocument): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete') || $this->canAccess($user, self::MODULE, 'update');
    }
}
