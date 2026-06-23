<?php

namespace App\Policies;

use App\Models\SimulationImpediment;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class SimulationImpedimentPolicy
{
    use ChecksPermissions;

    public function view(User $user, SimulationImpediment $simulationImpediment): bool
    {
        $session = $simulationImpediment->simulationSession;

        if ($user->hasRole('candidate')) {
            return $session !== null
                && $session->belongsToUser($user)
                && $this->canAccess($user, 'simulator', 'view');
        }

        return $this->canAccess($user, 'simulator', 'view');
    }
}
