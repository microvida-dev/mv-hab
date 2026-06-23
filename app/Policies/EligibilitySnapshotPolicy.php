<?php

namespace App\Policies;

use App\Models\EligibilitySnapshot;
use App\Models\User;

class EligibilitySnapshotPolicy
{
    public function view(User $user, EligibilitySnapshot $snapshot): bool
    {
        return ! $user->hasRole('candidate')
            && $user->can('view', $snapshot->check);
    }
}
