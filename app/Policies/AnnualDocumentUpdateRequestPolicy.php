<?php

namespace App\Policies;

use App\Models\AnnualDocumentUpdateRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;

class AnnualDocumentUpdateRequestPolicy
{
    use ChecksFinanceAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewFinance($user);
    }

    public function view(User $user, AnnualDocumentUpdateRequest $annualDocumentUpdateRequest): bool
    {
        return $user->hasRole('candidate') ? $this->ownsFinanceRecord($user, $annualDocumentUpdateRequest) && $this->canViewFinance($user) : $this->canViewFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateFinance($user);
    }

    public function update(User $user, AnnualDocumentUpdateRequest $annualDocumentUpdateRequest): bool
    {
        return $user->hasRole('candidate')
            ? $this->ownsFinanceRecord($user, $annualDocumentUpdateRequest)
            : $this->canManageFinance($user);
    }
}
