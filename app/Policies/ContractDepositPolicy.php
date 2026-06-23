<?php

namespace App\Policies;

use App\Models\ContractDeposit;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ContractDepositPolicy
{
    use ChecksPermissions;

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
}
