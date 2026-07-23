<?php

namespace App\Policies;

use App\Models\HousingApplication;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class HousingApplicationPolicy
{
    use ChecksPermissions;

    private const MODULE = 'applications';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, HousingApplication $housingApplication): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, HousingApplication $housingApplication): bool
    {
        return $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, HousingApplication $housingApplication): bool
    {
        return $this->canAccess($user, self::MODULE, 'delete');
    }

    public function approve(User $user, HousingApplication $housingApplication): bool
    {
        return $this->canAccess($user, self::MODULE, 'approve');
    }

    public function reject(User $user, HousingApplication $housingApplication): bool
    {
        return $this->canAccess($user, self::MODULE, 'reject');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $user->municipality_id !== null;
    }

    public function viewBackoffice(User $user, HousingApplication $application): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsHousingApplication($user, $application);
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'create')
            && $user->municipality_id !== null;
    }

    public function updateBackoffice(User $user, HousingApplication $application): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update')
            && $this->municipalScope->ownsHousingApplication($user, $application);
    }

    public function deleteBackoffice(User $user, HousingApplication $application): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'delete')
            && $this->municipalScope->ownsHousingApplication($user, $application);
    }
}
