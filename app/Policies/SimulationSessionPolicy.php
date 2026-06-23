<?php

namespace App\Policies;

use App\Models\SimulationSession;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class SimulationSessionPolicy
{
    use ChecksPermissions;

    private const MODULE = 'simulator';

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, SimulationSession $simulationSession): bool
    {
        if ($user->hasRole('candidate')) {
            return $simulationSession->belongsToUser($user)
                && $this->canAccess($user, self::MODULE, 'view');
        }

        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, SimulationSession $simulationSession): bool
    {
        return $simulationSession->belongsToUser($user)
            && $this->canAccess($user, self::MODULE, 'update');
    }

    public function viewInsights(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view');
    }
}
