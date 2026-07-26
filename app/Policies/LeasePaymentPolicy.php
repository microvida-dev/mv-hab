<?php

namespace App\Policies;

use App\Models\LeasePayment;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class LeasePaymentPolicy
{
    use ChecksFinanceAccess;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canViewFinance($user);
    }

    public function view(User $user, LeasePayment $leasePayment): bool
    {
        return $user->hasRole('candidate') ? $this->ownsFinanceRecord($user, $leasePayment) && $this->canViewFinance($user) : $this->canViewFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateFinance($user);
    }

    public function update(User $user, LeasePayment $leasePayment): bool
    {
        return $this->canManageFinance($user);
    }

    public function approve(User $user, LeasePayment $leasePayment): bool
    {
        return $this->canManageFinance($user) && $this->canAccess($user, 'finance', 'approve');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'payments', 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, LeasePayment $payment): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsLeasePayment($user, $payment);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->canMutateBackoffice($user, 'create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function allocateBackoffice(User $user, LeasePayment $payment): bool
    {
        return $this->canMutateBackoffice($user, 'allocate')
            && $this->municipalScope->ownsLeasePayment($user, $payment);
    }

    public function confirmBackoffice(User $user, LeasePayment $payment): bool
    {
        return $this->canMutateBackoffice($user, 'confirm')
            && $this->municipalScope->ownsLeasePayment($user, $payment);
    }

    public function reverseBackoffice(User $user, LeasePayment $payment): bool
    {
        return $this->canMutateBackoffice($user, 'reverse')
            && $this->municipalScope->ownsLeasePayment($user, $payment);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'payments', $action);
    }
}
