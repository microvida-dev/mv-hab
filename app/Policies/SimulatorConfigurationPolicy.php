<?php

namespace App\Policies;

use App\Models\SimulatorConfiguration;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class SimulatorConfigurationPolicy
{
    use ChecksPermissions;

    public function view(User $user, SimulatorConfiguration $simulatorConfiguration): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'simulator', 'view');
    }

    public function update(User $user, SimulatorConfiguration $simulatorConfiguration): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'simulator', 'update');
    }
}
