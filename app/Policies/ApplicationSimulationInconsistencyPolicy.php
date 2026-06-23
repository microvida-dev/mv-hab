<?php

namespace App\Policies;

use App\Models\ApplicationSimulationInconsistency;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ApplicationSimulationInconsistencyPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'candidate_experience', 'view');
    }

    public function view(User $user, ApplicationSimulationInconsistency $inconsistency): bool
    {
        return $user->hasRole('candidate')
            ? $inconsistency->user_id === $user->id && $this->canAccess($user, 'candidate_experience', 'view')
            : $this->canAccess($user, 'candidate_experience', 'view');
    }

    public function resolve(User $user, ApplicationSimulationInconsistency $inconsistency): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'candidate_experience', 'update');
    }
}
