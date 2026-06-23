<?php

namespace App\Policies;

use App\Models\Hearing;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class HearingPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'complaints', 'view');
    }

    public function view(User $user, Hearing $hearing): bool
    {
        return $user->hasRole('candidate')
            ? $hearing->user_id === $user->id && $hearing->candidate_visible && $this->canAccess($user, 'complaints', 'view')
            : $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'complaints', 'create');
    }

    public function update(User $user, Hearing $hearing): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'complaints', 'update');
    }
}
