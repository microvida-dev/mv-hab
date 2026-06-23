<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait ChecksFinanceAccess
{
    use ChecksPermissions;

    protected function canViewFinance(User $user): bool
    {
        return $this->canAccess($user, 'finance', 'view') || $this->canAccess($user, 'payments', 'view');
    }

    protected function canManageFinance(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && ($user->hasRole(['administrator', 'financial_manager']) || $this->canAccess($user, 'finance', 'update'))
            && ($this->canAccess($user, 'finance', 'update') || $this->canAccess($user, 'payments', 'update'));
    }

    protected function canCreateFinance(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && ($user->hasRole(['administrator', 'financial_manager']) || $this->canAccess($user, 'finance', 'create'));
    }

    protected function ownsFinanceRecord(User $user, Model $model): bool
    {
        return (int) ($model->user_id ?? 0) === (int) $user->id;
    }
}
