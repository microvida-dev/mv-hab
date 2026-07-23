<?php

namespace App\Policies;

use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class AdministrativeProcessPolicy
{
    use ChecksPermissions;

    private const MODULE = 'administrative_processes';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, AdministrativeProcess $administrativeProcess): bool
    {
        if ($user->hasRole('candidate')) {
            return $administrativeProcess->user_id === $user->id
                && $this->canAccess($user, self::MODULE, 'view');
        }

        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'create');
    }

    public function createForApplication(User $user, Application $application): bool
    {
        return $this->create($user)
            && $this->municipalScope->ownsApplication($user, $application);
    }

    public function update(User $user, AdministrativeProcess $administrativeProcess): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update')
            && ! $administrativeProcess->isClosed();
    }

    public function audit(User $user, AdministrativeProcess $administrativeProcess): bool
    {
        return $this->canAccess($user, self::MODULE, 'audit') || $this->view($user, $administrativeProcess);
    }

    public function viewBackoffice(
        User $user,
        AdministrativeProcess $administrativeProcess,
    ): bool {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view');
    }

    public function auditBackoffice(
        User $user,
        AdministrativeProcess $administrativeProcess,
    ): bool {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'audit')
                || $this->canAccess($user, self::MODULE, 'view')
            );
    }
}
