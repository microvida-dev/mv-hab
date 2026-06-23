<?php

namespace App\Policies;

use App\Models\ApplicationSnapshot;
use App\Models\User;

class ApplicationSnapshotPolicy
{
    public function view(User $user, ApplicationSnapshot $snapshot): bool
    {
        return $user->can('view', $snapshot->application);
    }
}
