<?php

namespace App\Policies;

use App\Enums\IncomeChangeStatus;
use App\Models\IncomeChangeDeclaration;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class IncomeChangeDeclarationPolicy
{
    use ChecksFinanceAccess;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'finance', 'income_changes.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, IncomeChangeDeclaration $declaration): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsIncomeChangeDeclaration($user, $declaration);
    }

    public function approveBackoffice(User $user, IncomeChangeDeclaration $declaration): bool
    {
        return $this->canMutateBackoffice($user, 'income_changes.approve')
            && $this->municipalScope->ownsIncomeChangeDeclaration($user, $declaration);
    }

    public function rejectBackoffice(User $user, IncomeChangeDeclaration $declaration): bool
    {
        return $this->canMutateBackoffice($user, 'income_changes.reject')
            && $this->municipalScope->ownsIncomeChangeDeclaration($user, $declaration);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'finance', $action);
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
