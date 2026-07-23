<?php

namespace App\Policies;

use App\Models\ApplicationSimulationInconsistency;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ApplicationSimulationInconsistencyPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'administrative_processes', 'view')
            && $user->municipality_id !== null;
    }

    public function decideBackoffice(
        User $user,
        ApplicationSimulationInconsistency $inconsistency,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'administrative_processes', 'decide')
            && $this->municipalScope->ownsApplicationSimulationInconsistency($user, $inconsistency);
    }
}
