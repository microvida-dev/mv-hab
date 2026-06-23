<?php

namespace App\Policies;

use App\Models\RentSchedule;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;

class RentSchedulePolicy
{
    use ChecksFinanceAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewFinance($user);
    }

    public function view(User $user, RentSchedule $rentSchedule): bool
    {
        return $user->hasRole('candidate') ? $this->ownsFinanceRecord($user, $rentSchedule) && $this->canViewFinance($user) : $this->canViewFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateFinance($user);
    }

    public function update(User $user, RentSchedule $rentSchedule): bool
    {
        return $this->canManageFinance($user);
    }
}
