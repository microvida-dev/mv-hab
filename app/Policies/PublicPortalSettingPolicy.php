<?php

namespace App\Policies;

use App\Models\PublicPortalSetting;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class PublicPortalSettingPolicy
{
    use ChecksPermissions;

    private const MODULE = 'settings';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, PublicPortalSetting $publicPortalSetting): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function update(User $user, PublicPortalSetting $publicPortalSetting): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function updateAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view');
    }

    public function updateAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update');
    }
}
