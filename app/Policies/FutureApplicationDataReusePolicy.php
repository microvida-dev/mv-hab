<?php

namespace App\Policies;

use App\Models\FutureApplicationDataReuse;
use App\Models\User;

class FutureApplicationDataReusePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('applications', 'view');
    }

    public function view(User $user, FutureApplicationDataReuse $reuse): bool
    {
        return $reuse->user_id === $user->id || $user->hasPermissionTo('applications', 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate') || $user->hasPermissionTo('applications', 'create');
    }

    public function update(User $user, FutureApplicationDataReuse $reuse): bool
    {
        return $reuse->user_id === $user->id;
    }
}
