<?php

namespace App\Policies;

use App\Models\SimulationSession;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class SimulationSessionPolicy
{
    use ChecksPermissions;

    private const MODULE = 'simulator';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $user->municipality_id !== null;
    }

    public function viewBackoffice(User $user, SimulationSession $session): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsSimulationSession($user, $session);
    }
}
