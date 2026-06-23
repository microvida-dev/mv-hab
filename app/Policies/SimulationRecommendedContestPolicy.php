<?php

namespace App\Policies;

use App\Models\SimulationRecommendedContest;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class SimulationRecommendedContestPolicy
{
    use ChecksPermissions;

    public function view(User $user, SimulationRecommendedContest $simulationRecommendedContest): bool
    {
        $session = $simulationRecommendedContest->simulationSession;

        if ($user->hasRole('candidate')) {
            return $session !== null
                && $session->belongsToUser($user)
                && $this->canAccess($user, 'simulator', 'view');
        }

        return $this->canAccess($user, 'simulator', 'view');
    }
}
