<?php

namespace App\Policies;

use App\Models\ReserveListEntry;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ReserveListEntryPolicy
{
    use ChecksPermissions;

    public function view(User $user, ReserveListEntry $entry): bool
    {
        return $user->hasRole('candidate')
            ? $entry->user_id === $user->id && $this->canAccess($user, 'allocations', 'view')
            : $this->canAccess($user, 'allocations', 'view');
    }
}
