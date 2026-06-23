<?php

namespace App\Policies;

use App\Models\ApplicationPublicStatusSnapshot;
use App\Models\User;

class ApplicationPublicStatusSnapshotPolicy
{
    public function view(User $user, ApplicationPublicStatusSnapshot $snapshot): bool
    {
        return $snapshot->application?->user_id === $user->id || $user->hasPermissionTo('applications', 'view');
    }

    public function update(User $user, ApplicationPublicStatusSnapshot $snapshot): bool
    {
        return $user->hasPermissionTo('applications', 'update') || $user->hasPermissionTo('settings', 'update');
    }
}
