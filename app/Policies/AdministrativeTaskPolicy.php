<?php

namespace App\Policies;

use App\Models\AdministrativeTask;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class AdministrativeTaskPolicy
{
    use ChecksPermissions;

    private const MODULE = 'administrative_processes';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, AdministrativeTask $administrativeTask): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'update');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $user->municipality_id !== null;
    }

    public function updateBackoffice(User $user, AdministrativeTask $task): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update')
            && $this->municipalScope->ownsAdministrativeTask($user, $task);
    }

    public function completeBackoffice(User $user, AdministrativeTask $task): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'complete')
            && $this->municipalScope->ownsAdministrativeTask($user, $task);
    }

    public function cancelBackoffice(User $user, AdministrativeTask $task): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'cancel')
            && $this->municipalScope->ownsAdministrativeTask($user, $task);
    }
}
