<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\RentSchedule;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class RentSchedulePolicy
{
    use ChecksFinanceAccess;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'finance', 'schedules.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, RentSchedule $schedule): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsRentSchedule($user, $schedule);
    }

    public function generateBackoffice(User $user, Contract $contract): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'finance', 'schedules.generate')
            && $this->municipalScope->ownsContract($user, $contract);
    }
}
