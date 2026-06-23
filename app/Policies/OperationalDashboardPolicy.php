<?php

namespace App\Policies;

use App\Models\User;

class OperationalDashboardPolicy
{
    public function view(User $user): bool
    {
        return ! $user->hasRole('candidate') && ($user->hasPermission('reports.view') || $user->hasPermission('applications.view'));
    }
}
