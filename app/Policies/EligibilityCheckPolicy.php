<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\EligibilityCheck;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class EligibilityCheckPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'eligibility', 'view');
    }

    public function view(User $user, EligibilityCheck $check): bool
    {
        if ($user->hasRole('candidate')) {
            return $check->user_id === $user->id
                && $this->canAccess($user, 'eligibility', 'view');
        }

        return $this->viewAny($user);
    }

    public function runPreCheck(User $user): bool
    {
        return $user->hasRole('candidate')
            && $this->canAccess($user, 'eligibility', 'create');
    }

    public function runFormal(User $user, Application $application): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'eligibility', 'create');
    }

    public function rerun(User $user, EligibilityCheck $check): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'eligibility', 'update');
    }
}
