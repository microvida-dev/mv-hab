<?php

namespace App\Policies;

use App\Enums\IncomeChangeStatus;
use App\Models\IncomeChangeDeclaration;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;

class IncomeChangeDeclarationPolicy
{
    use ChecksFinanceAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewFinance($user);
    }

    public function view(User $user, IncomeChangeDeclaration $incomeChangeDeclaration): bool
    {
        return $user->hasRole('candidate') ? $this->ownsFinanceRecord($user, $incomeChangeDeclaration) && $this->canViewFinance($user) : $this->canViewFinance($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate') && $this->canAccess($user, 'finance', 'create');
    }

    public function update(User $user, IncomeChangeDeclaration $incomeChangeDeclaration): bool
    {
        return $user->hasRole('candidate')
            ? $this->ownsFinanceRecord($user, $incomeChangeDeclaration) && $this->statusIsIn($incomeChangeDeclaration, ['draft', 'submitted'])
            : $this->canManageFinance($user);
    }

    /** @param  list<string>  $statuses */
    private function statusIsIn(IncomeChangeDeclaration $declaration, array $statuses): bool
    {
        $status = $declaration->getAttribute('status');

        if ($status instanceof IncomeChangeStatus) {
            return in_array($status->value, $statuses, true);
        }

        return is_string($status) && in_array($status, $statuses, true);
    }
}
