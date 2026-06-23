<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\ControlledWithdrawal;
use App\Models\User;

class ControlledWithdrawalPolicy
{
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
}
