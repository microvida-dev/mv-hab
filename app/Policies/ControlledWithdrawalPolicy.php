<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\ControlledWithdrawal;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ControlledWithdrawalPolicy
{
    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('applications', 'view');
    }

    public function create(User $user, Application $application): bool
    {
        return $application->user_id === $user->id && $application->status->canBeWithdrawn();
    }

    public function view(User $user, ControlledWithdrawal $withdrawal): bool
    {
        return $withdrawal->user_id === $user->id || $user->hasPermissionTo('applications', 'view');
    }

    public function update(User $user, ControlledWithdrawal $withdrawal): bool
    {
        return $withdrawal->user_id === $user->id || $user->hasPermissionTo('applications', 'update');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->municipality_id !== null
            && $user->hasPermissionTo('allocations', 'view');
    }

    public function viewBackoffice(User $user, ControlledWithdrawal $withdrawal): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsControlledWithdrawal($user, $withdrawal);
    }

    public function processBackoffice(User $user, ControlledWithdrawal $withdrawal): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $user->hasPermissionTo('allocations', 'process_withdrawal')
            && $this->municipalScope->ownsControlledWithdrawal($user, $withdrawal);
    }
}
