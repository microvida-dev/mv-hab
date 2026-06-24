<?php

namespace App\Policies;

use App\Models\User;

class UserAdministrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user, User $target): bool
    {
        return $this->can($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(User $user, User $target): bool
    {
        return $this->can($user, 'update');
    }

    public function deactivate(User $user, User $target): bool
    {
        return $this->can($user, 'deactivate');
    }

    public function reactivate(User $user, User $target): bool
    {
        return $this->can($user, 'reactivate');
    }

    public function forceMfa(User $user, User $target): bool
    {
        return $this->can($user, 'force_mfa');
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $this->can($user, 'reset_password');
    }

    private function can(User $user, string $action): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission("users.{$action}");
    }
}
