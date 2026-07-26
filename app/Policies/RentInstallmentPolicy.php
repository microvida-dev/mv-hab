<?php

namespace App\Policies;

use App\Models\RentInstallment;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class RentInstallmentPolicy
{
    use ChecksFinanceAccess;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canViewFinance($user);
    }

    public function view(User $user, RentInstallment $rentInstallment): bool
    {
        return $user->hasRole('candidate') ? $this->ownsFinanceRecord($user, $rentInstallment) && $this->canViewFinance($user) : $this->canViewFinance($user);
    }

    public function update(User $user, RentInstallment $rentInstallment): bool
    {
        return $this->canManageFinance($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'finance', 'installments.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, RentInstallment $installment): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsRentInstallment($user, $installment);
    }

    public function issueBackoffice(User $user, RentInstallment $installment): bool
    {
        return $this->canMutateBackoffice($user, 'installments.issue')
            && $this->municipalScope->ownsRentInstallment($user, $installment);
    }

    public function waiveBackoffice(User $user, RentInstallment $installment): bool
    {
        return $this->canMutateBackoffice($user, 'installments.waive')
            && $this->municipalScope->ownsRentInstallment($user, $installment);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'finance', $action);
    }
}
