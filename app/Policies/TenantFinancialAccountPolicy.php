<?php

namespace App\Policies;

use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class TenantFinancialAccountPolicy
{
    use ChecksFinanceAccess;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canViewFinance($user);
    }

    public function view(User $user, TenantFinancialAccount $tenantFinancialAccount): bool
    {
        return $user->hasRole('candidate')
            ? $this->ownsFinanceRecord($user, $tenantFinancialAccount) && $this->canViewFinance($user)
            : $this->canViewFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateFinance($user);
    }

    public function update(User $user, TenantFinancialAccount $tenantFinancialAccount): bool
    {
        return $this->canManageFinance($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'finance', 'accounts.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, TenantFinancialAccount $account): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsTenantFinancialAccount($user, $account);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->canMutateBackoffice($user, 'accounts.create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function detectArrearsBackoffice(User $user, TenantFinancialAccount $account): bool
    {
        return $this->canMutateBackoffice($user, 'accounts.detect_arrears')
            && $this->municipalScope->ownsTenantFinancialAccount($user, $account);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'finance', $action);
    }
}
