<?php

namespace App\Policies;

use App\Models\PublicPortalLink;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class PublicPortalLinkPolicy
{
    use ChecksPermissions;

    private const MODULE = 'settings';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, PublicPortalLink $publicPortalLink): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, PublicPortalLink $publicPortalLink): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, PublicPortalLink $publicPortalLink): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }
}
