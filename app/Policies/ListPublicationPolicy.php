<?php

namespace App\Policies;

use App\Models\ListPublication;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ListPublicationPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'public_lists', 'view');
    }

    public function view(User $user, ListPublication $publication): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'public_lists', 'publish');
    }
}
