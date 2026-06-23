<?php

namespace App\Policies;

use App\Models\ProvisionalListEntry;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ProvisionalListEntryPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'public_lists', 'view');
    }

    public function view(User $user, ProvisionalListEntry $entry): bool
    {
        return $user->hasRole('candidate')
            ? $entry->user_id === $user->id && $this->canAccess($user, 'public_lists', 'view')
            : $this->viewAny($user);
    }

    public function update(User $user, ProvisionalListEntry $entry): bool
    {
        return false;
    }
}
