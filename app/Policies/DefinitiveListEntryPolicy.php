<?php

namespace App\Policies;

use App\Models\DefinitiveListEntry;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class DefinitiveListEntryPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'public_lists', 'view');
    }

    public function view(User $user, DefinitiveListEntry $entry): bool
    {
        return $user->hasRole('candidate')
            ? $entry->user_id === $user->id && $this->canAccess($user, 'public_lists', 'view')
            : $this->viewAny($user);
    }

    public function update(User $user, DefinitiveListEntry $entry): bool
    {
        return false;
    }
}
