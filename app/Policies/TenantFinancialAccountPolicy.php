<?php

namespace App\Policies;

use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;

class TenantFinancialAccountPolicy
{
    use ChecksFinanceAccess;

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
}
