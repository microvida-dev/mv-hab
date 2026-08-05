<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Services\Access\AccessMunicipalScopeService;

class TeamManagementPolicy
{
    public function __construct(private readonly AccessMunicipalScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view') && $this->municipalScope->hasMunicipality($user);
    }

    public function view(User $user, MunicipalTeam $team): bool
    {
        return $this->canTeam($user, $team, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create') && $this->municipalScope->hasMunicipality($user);
    }

    public function update(User $user, MunicipalTeam $team): bool
    {
        return $this->canTeam($user, $team, 'update');
    }

    public function manageMembers(User $user, MunicipalTeam $team): bool
    {
        return $this->canTeam($user, $team, 'manage_members');
    }

    private function canTeam(User $user, MunicipalTeam $team, string $action): bool
    {
        return $this->can($user, $action) && $this->municipalScope->ownsTeam($user, $team);
    }

    private function can(User $user, string $action): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission("teams.{$action}");
    }
}
