<?php

namespace App\Policies;

use App\Models\InternalAlert;
use App\Models\User;

class InternalAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && ($user->hasPermission('reports.view') || $user->hasPermission('applications.view'));
    }

    public function view(User $user, InternalAlert $alert): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, InternalAlert $alert): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && ($user->hasPermission('reports.update') || $user->hasPermission('applications.update'));
    }
}
