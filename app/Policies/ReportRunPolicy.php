<?php

namespace App\Policies;

use App\Models\ReportRun;
use App\Models\User;

class ReportRunPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('reports.view');
    }

    public function view(User $user, ReportRun $run): bool
    {
        return $user->can('view', $run->definition);
    }
}
