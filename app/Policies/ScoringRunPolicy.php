<?php

namespace App\Policies;

use App\Models\ScoringRun;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ScoringRunPolicy
{
    use ChecksPermissions;

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
}
