<?php

namespace App\Policies;

use App\Models\SimulationResult;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class SimulationResultPolicy
{
    use ChecksPermissions;

    public function view(User $user, SimulationResult $simulationResult): bool
    {
        $session = $simulationResult->simulationSession;

        if ($user->hasRole('candidate')) {
            return $session !== null
                && $session->belongsToUser($user)
                && $this->canAccess($user, 'simulator', 'view');
        }

        return $this->canAccess($user, 'simulator', 'view');
    }
}
