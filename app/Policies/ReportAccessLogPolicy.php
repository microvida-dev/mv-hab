<?php

namespace App\Policies;

use App\Models\ReportAccessLog;
use App\Models\User;

class ReportAccessLogPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('reports.audit');
    }

    public function view(User $user, ReportAccessLog $log): bool
    {
        return $this->viewAny($user);
    }
}
