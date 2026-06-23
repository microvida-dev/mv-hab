<?php

namespace App\Policies;

use App\Models\AdministrativeDecision;
use App\Models\Application;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class AdministrativeDecisionPolicy
{
    use ChecksPermissions;

    private const MODULE = 'administrative_processes';

    public function view(User $user, AdministrativeDecision $administrativeDecision): bool
    {
        if ($user->hasRole('candidate')) {
            $application = $administrativeDecision->application;

            return $application instanceof Application
                && $application->user_id === $user->id
                && $administrativeDecision->candidate_visible
                && $this->canAccess($user, self::MODULE, 'view');
        }

        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'create');
    }

    public function approve(User $user, AdministrativeDecision $administrativeDecision): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'approve');
    }
}
