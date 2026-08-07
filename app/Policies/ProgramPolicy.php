<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Platform\PlatformOperatorScopeService;

class ProgramPolicy
{
    use ChecksPermissions;

    private const MODULE = 'programs';

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, Program $program): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'create')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function update(User $user, Program $program): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, Program $program): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }

    public function publish(User $user, Program $program): bool
    {
        return $this->canAccess($user, self::MODULE, 'publish');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, Program $program): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->ownsProgram($user, $program);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->create($user);
    }

    public function updateBackoffice(User $user, Program $program): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update')
            && $this->municipalScope->ownsProgram($user, $program);
    }

    public function deleteBackoffice(User $user, Program $program): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'delete')
            && $this->municipalScope->ownsProgram($user, $program);
    }

    public function publishBackoffice(User $user, Program $program): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'publish')
            && $this->municipalScope->ownsProgram($user, $program);
    }
}
