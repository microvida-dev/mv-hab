<?php

namespace App\Policies;

use App\Models\RegularizationAgreement;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class RegularizationAgreementPolicy
{
    use ChecksFinanceAccess;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canViewFinance($user);
    }

    public function view(User $user, RegularizationAgreement $regularizationAgreement): bool
    {
        return $user->hasRole('candidate') ? $this->ownsFinanceRecord($user, $regularizationAgreement) && $this->canViewFinance($user) : $this->canViewFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateFinance($user);
    }

    public function update(User $user, RegularizationAgreement $regularizationAgreement): bool
    {
        return $this->canManageFinance($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'finance', 'regularizations.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, RegularizationAgreement $agreement): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsRegularizationAgreement($user, $agreement);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->canMutateBackoffice($user, 'regularizations.create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function approveBackoffice(User $user, RegularizationAgreement $agreement): bool
    {
        return $this->canMutateBackoffice($user, 'regularizations.approve')
            && $this->municipalScope->ownsRegularizationAgreement($user, $agreement);
    }

    public function cancelBackoffice(User $user, RegularizationAgreement $agreement): bool
    {
        return $this->canMutateBackoffice($user, 'regularizations.cancel')
            && $this->municipalScope->ownsRegularizationAgreement($user, $agreement);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'finance', $action);
    }
}
