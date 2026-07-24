<?php

namespace App\Policies;

use App\Models\ContractClause;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ContractClausePolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'contracts', 'view');
    }

    public function view(User $user, ContractClause $contractClause): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'create');
    }

    public function update(User $user, ContractClause $contractClause): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'update');
    }

    public function activate(User $user, ContractClause $contractClause): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'approve');
    }

    public function archive(User $user, ContractClause $contractClause): bool
    {
        return $this->update($user, $contractClause);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'contracts', 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, ContractClause $contractClause): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsContractClause($user, $contractClause);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->canMutate($user, 'create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function updateBackoffice(User $user, ContractClause $contractClause): bool
    {
        return $this->canMutate($user, 'update')
            && $this->municipalScope->ownsContractClause($user, $contractClause);
    }

    public function activateBackoffice(User $user, ContractClause $contractClause): bool
    {
        return $this->canMutate($user, 'clauses.activate')
            && $this->municipalScope->ownsContractClause($user, $contractClause);
    }

    public function archiveBackoffice(User $user, ContractClause $contractClause): bool
    {
        return $this->canMutate($user, 'clauses.archive')
            && $this->municipalScope->ownsContractClause($user, $contractClause);
    }

    private function canMutate(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'contracts', $action);
    }
}
