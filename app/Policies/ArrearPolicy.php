<?php

namespace App\Policies;

use App\Models\Arrear;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;

class ArrearPolicy
{
    use ChecksFinanceAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewFinance($user);
    }

    public function view(User $user, Arrear $arrear): bool
    {
        return $user->hasRole('candidate') ? $this->ownsFinanceRecord($user, $arrear) && $this->canViewFinance($user) : $this->canViewFinance($user);
    }

    public function update(User $user, Arrear $arrear): bool
    {
        return $this->canManageFinance($user);
    }
}
