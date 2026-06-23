<?php

namespace App\Policies;

use App\Models\ProcessAction;
use App\Models\User;

class ProcessActionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('applications', 'view');
    }

    public function view(User $user, ProcessAction $action): bool
    {
        return $action->user_id === $user->id || $user->hasPermissionTo('applications', 'view');
    }

    public function update(User $user, ProcessAction $action): bool
    {
        return $action->user_id === $user->id || $user->hasPermissionTo('applications', 'update');
    }
}
