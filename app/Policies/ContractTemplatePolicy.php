<?php

namespace App\Policies;

use App\Models\ContractTemplate;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ContractTemplatePolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'contracts', 'view');
    }

    public function view(User $user, ContractTemplate $contractTemplate): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'create');
    }

    public function update(User $user, ContractTemplate $contractTemplate): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'update');
    }

    public function activate(User $user, ContractTemplate $contractTemplate): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'approve');
    }

    public function archive(User $user, ContractTemplate $contractTemplate): bool
    {
        return $this->update($user, $contractTemplate);
    }

    public function duplicate(User $user, ContractTemplate $contractTemplate): bool
    {
        return $this->create($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'contracts', 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, ContractTemplate $contractTemplate): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsContractTemplate($user, $contractTemplate);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->canMutate($user, 'create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function updateBackoffice(User $user, ContractTemplate $contractTemplate): bool
    {
        return $this->canMutate($user, 'update')
            && $this->municipalScope->ownsContractTemplate($user, $contractTemplate);
    }

    public function activateBackoffice(User $user, ContractTemplate $contractTemplate): bool
    {
        return $this->canMutate($user, 'templates.activate')
            && $this->municipalScope->ownsContractTemplate($user, $contractTemplate);
    }

    public function archiveBackoffice(User $user, ContractTemplate $contractTemplate): bool
    {
        return $this->canMutate($user, 'templates.archive')
            && $this->municipalScope->ownsContractTemplate($user, $contractTemplate);
    }

    public function duplicateBackoffice(User $user, ContractTemplate $contractTemplate): bool
    {
        return $this->canMutate($user, 'templates.duplicate')
            && $this->municipalScope->ownsContractTemplate($user, $contractTemplate);
    }

    private function canMutate(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'contracts', $action);
    }
}
