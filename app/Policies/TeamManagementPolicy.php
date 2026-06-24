<?php

namespace App\Policies;

use App\Models\MunicipalTeam;
use App\Models\User;

class TeamManagementPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user, MunicipalTeam $team): bool
    {
        return $this->can($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(User $user, MunicipalTeam $team): bool
    {
        return $this->can($user, 'update');
    }

    public function manageMembers(User $user, MunicipalTeam $team): bool
    {
        return $this->can($user, 'manage_members');
    }

    private function can(User $user, string $action): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission("teams.{$action}");
    }
}
