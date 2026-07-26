<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ContractPolicy
{
    use ChecksPermissions;

    private const MODULE = 'contracts';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, Contract $contract): bool
    {
        return $user->hasRole('candidate')
            ? $contract->user_id === $user->id && $this->canAccess($user, self::MODULE, 'view')
            : $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, Contract $contract): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'update');
    }

    public function delete(User $user, Contract $contract): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'delete');
    }

    public function approve(User $user, Contract $contract): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'approve');
    }

    public function issue(User $user, Contract $contract): bool
    {
        return $this->update($user, $contract);
    }

    public function activate(User $user, Contract $contract): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'approve');
    }

    public function sign(User $user, Contract $contract): bool
    {
        return $this->update($user, $contract);
    }

    public function generateDocument(User $user, Contract $contract): bool
    {
        return $this->update($user, $contract);
    }

    public function generateBackoffice(User $user, Contract $contract): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'generate')
            && $this->municipalScope->ownsContract($user, $contract);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, Contract $contract): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsContract($user, $contract);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->canMutateBackoffice($user, 'create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function updateBackoffice(User $user, Contract $contract): bool
    {
        return $this->canMutateBackoffice($user, 'update')
            && $this->municipalScope->ownsContract($user, $contract);
    }

    public function deleteBackoffice(User $user, Contract $contract): bool
    {
        return $this->canMutateBackoffice($user, 'delete')
            && $this->municipalScope->ownsContract($user, $contract);
    }

    public function issueBackoffice(User $user, Contract $contract): bool
    {
        return $this->canMutateBackoffice($user, 'issue')
            && $this->municipalScope->ownsContract($user, $contract);
    }

    public function activateBackoffice(User $user, Contract $contract): bool
    {
        return $this->canMutateBackoffice($user, 'activate')
            && $this->municipalScope->ownsContract($user, $contract);
    }

    public function suspendBackoffice(User $user, Contract $contract): bool
    {
        return $this->canMutateBackoffice($user, 'suspend')
            && $this->municipalScope->ownsContract($user, $contract);
    }

    public function terminateBackoffice(User $user, Contract $contract): bool
    {
        return $this->canMutateBackoffice($user, 'terminate')
            && $this->municipalScope->ownsContract($user, $contract);
    }

    public function cancelBackoffice(User $user, Contract $contract): bool
    {
        return $this->canMutateBackoffice($user, 'cancel')
            && $this->municipalScope->ownsContract($user, $contract);
    }

    public function signBackoffice(User $user, Contract $contract): bool
    {
        return $this->canMutateBackoffice($user, 'sign')
            && $this->municipalScope->ownsContract($user, $contract);
    }

    public function validateBackoffice(User $user, Contract $contract): bool
    {
        return $this->canMutateBackoffice($user, 'validations.create')
            && $this->municipalScope->ownsContract($user, $contract);
    }

    public function viewMaintenanceReportsBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'maintenance_reports.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, $action);
    }
}
