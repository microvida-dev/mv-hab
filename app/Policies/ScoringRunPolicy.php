<?php

namespace App\Policies;

use App\Models\ScoringRun;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ScoringRunPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'scoring', 'view');
    }

    public function view(User $user, ScoringRun $run): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'create');
    }

    public function run(User $user, ScoringRun $run): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && ($this->canAccess($user, 'scoring', 'create') || $this->canAccess($user, 'scoring', 'approve'));
    }

    public function lock(User $user, ScoringRun $run): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'approve');
    }

    public function cancel(User $user, ScoringRun $run): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'reject');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->municipality_id !== null
            && $this->canAccess($user, 'scoring', 'view');
    }

    public function viewBackoffice(User $user, ScoringRun $run): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsScoringRun($user, $run);
    }

    public function runAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $user->municipality_id !== null
            && $this->canAccess($user, 'scoring', 'run');
    }

    public function runBackoffice(User $user, ScoringRun $run): bool
    {
        return $this->runAnyBackoffice($user)
            && $this->municipalScope->ownsScoringRun($user, $run);
    }

    public function lockBackoffice(User $user, ScoringRun $run): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'lock')
            && $this->municipalScope->ownsScoringRun($user, $run);
    }

    public function cancelBackoffice(User $user, ScoringRun $run): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'cancel')
            && $this->municipalScope->ownsScoringRun($user, $run);
    }
}
