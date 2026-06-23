<?php

namespace App\Policies;

use App\Models\DefaultNotice;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;

class DefaultNoticePolicy
{
    use ChecksFinanceAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewFinance($user);
    }

    public function view(User $user, DefaultNotice $defaultNotice): bool
    {
        return $user->hasRole('candidate')
            ? $defaultNotice->candidate_visible && $this->ownsFinanceRecord($user, $defaultNotice) && $this->canViewFinance($user)
            : $this->canViewFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateFinance($user);
    }

    public function update(User $user, DefaultNotice $defaultNotice): bool
    {
        return $this->canManageFinance($user);
    }
}
