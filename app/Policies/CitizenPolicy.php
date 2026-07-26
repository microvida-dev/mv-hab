<?php

namespace App\Policies;

use App\Models\Citizen;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class CitizenPolicy
{
    use ChecksPermissions;

    private const MODULE = 'citizens';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, Citizen $citizen): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, Citizen $citizen): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, Citizen $citizen): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $user->municipality_id !== null;
    }

    public function viewBackoffice(User $user, Citizen $citizen): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsCitizen($user, $citizen);
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'create')
            && $user->municipality_id !== null;
    }

    public function updateBackoffice(User $user, Citizen $citizen): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update')
            && $this->municipalScope->ownsCitizen($user, $citizen);
    }

    public function deleteBackoffice(User $user, Citizen $citizen): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'delete')
            && $this->municipalScope->ownsCitizen($user, $citizen);
    }
}
