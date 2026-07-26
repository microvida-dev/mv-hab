<?php

namespace App\Policies;

use App\Models\ContractDeposit;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ContractDepositPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function view(User $user, ContractDeposit $contractDeposit): bool
    {
        return $user->hasRole('candidate')
            ? $contractDeposit->user_id === $user->id && $this->canAccess($user, 'contracts', 'view')
            : $this->canAccess($user, 'contracts', 'view');
    }

    public function update(User $user, ContractDeposit $contractDeposit): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && ($user->hasRole(['administrator', 'financial_manager']) || $this->canAccess($user, 'contracts', 'approve'))
            && $this->canAccess($user, 'contracts', 'update');
    }

    public function approve(User $user, ContractDeposit $contractDeposit): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'contracts', 'approve');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'finance', 'deposits.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, ContractDeposit $deposit): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsContractDeposit($user, $deposit);
    }

    public function requestBackoffice(User $user, ContractDeposit $deposit): bool
    {
        return $this->canMutateBackoffice($user, 'deposits.request')
            && $this->municipalScope->ownsContractDeposit($user, $deposit);
    }

    public function markPaidBackoffice(User $user, ContractDeposit $deposit): bool
    {
        return $this->canMutateBackoffice($user, 'deposits.mark_paid')
            && $this->municipalScope->ownsContractDeposit($user, $deposit);
    }

    public function waiveBackoffice(User $user, ContractDeposit $deposit): bool
    {
        return $this->canMutateBackoffice($user, 'deposits.waive')
            && $this->municipalScope->ownsContractDeposit($user, $deposit);
    }

    public function cancelBackoffice(User $user, ContractDeposit $deposit): bool
    {
        return $this->canMutateBackoffice($user, 'deposits.cancel')
            && $this->municipalScope->ownsContractDeposit($user, $deposit);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'finance', $action);
    }
}
