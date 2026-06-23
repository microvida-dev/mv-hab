<?php

namespace App\Policies;

use App\Models\User;

class ExecutiveDashboardPolicy
{
    public function view(User $user): bool
    {
        return ! $user->hasRole('candidate') && ($user->hasPermission('reports.view_executive') || $user->hasPermission('reports.view'));
    }
}
