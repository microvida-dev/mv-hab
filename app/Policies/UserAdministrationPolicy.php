<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Services\Access\AccessMunicipalScopeService;

class UserAdministrationPolicy
{
    public function __construct(private readonly AccessMunicipalScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view') && $this->municipalScope->hasMunicipality($user);
    }

    public function view(User $user, User $target): bool
    {
        return $this->canTarget($user, $target, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create') && $this->municipalScope->hasMunicipality($user);
    }

    public function update(User $user, User $target): bool
    {
        return $this->canTarget($user, $target, 'update');
    }

    public function deactivate(User $user, User $target): bool
    {
        return $this->canTarget($user, $target, 'deactivate');
    }

    public function reactivate(User $user, User $target): bool
    {
        return $this->canTarget($user, $target, 'reactivate');
    }

    public function forceMfa(User $user, User $target): bool
    {
        return $this->canTarget($user, $target, 'force_mfa');
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $this->canTarget($user, $target, 'reset_password');
    }

    private function canTarget(User $user, User $target, string $action): bool
    {
        return $this->can($user, $action) && $this->municipalScope->ownsUser($user, $target);
    }

    private function can(User $user, string $action): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission("users.{$action}");
    }
}
