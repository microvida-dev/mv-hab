<?php

namespace App\Policies;

use App\Models\RegularizationAgreement;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;

class RegularizationAgreementPolicy
{
    use ChecksFinanceAccess;

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
}
