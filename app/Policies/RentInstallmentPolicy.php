<?php

namespace App\Policies;

use App\Models\RentInstallment;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;

class RentInstallmentPolicy
{
    use ChecksFinanceAccess;

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
}
