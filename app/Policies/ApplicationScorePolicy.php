<?php

namespace App\Policies;

use App\Models\ApplicationScore;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ApplicationScorePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'scoring', 'view');
    }

    public function view(User $user, ApplicationScore $score): bool
    {
        return $this->viewAny($user);
    }

    public function manualReview(User $user, ApplicationScore $score): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && ($this->canAccess($user, 'scoring', 'update') || $this->canAccess($user, 'scoring', 'approve'));
    }

    public function lock(User $user, ApplicationScore $score): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'approve');
    }
}
