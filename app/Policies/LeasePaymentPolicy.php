<?php

namespace App\Policies;

use App\Models\LeasePayment;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;

class LeasePaymentPolicy
{
    use ChecksFinanceAccess;

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
}
