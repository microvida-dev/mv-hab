<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WinnerRegistration;
use App\Policies\Concerns\ChecksPermissions;

class WinnerRegistrationPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, WinnerRegistration $winner): bool
    {
        return $user->hasRole('candidate')
            ? $winner->user_id === $user->id
            : $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'approve');
    }
}
