<?php

namespace App\Policies;

use App\Models\AdministrativeProcessNote;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class AdministrativeProcessNotePolicy
{
    use ChecksPermissions;

    private const MODULE = 'administrative_processes';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, AdministrativeProcessNote $administrativeProcessNote): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, AdministrativeProcessNote $administrativeProcessNote): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'delete');
    }

    public function updateBackoffice(User $user, AdministrativeProcessNote $note): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update')
            && $this->municipalScope->ownsAdministrativeProcessNote($user, $note);
    }

    public function deleteBackoffice(User $user, AdministrativeProcessNote $note): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'delete')
            && $this->municipalScope->ownsAdministrativeProcessNote($user, $note);
    }
}
