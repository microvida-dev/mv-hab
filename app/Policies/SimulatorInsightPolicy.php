<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class SimulatorInsightPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'simulator', 'view');
    }

    public function export(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'simulator', 'export');
    }
}
