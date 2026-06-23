<?php

namespace App\Policies;

use App\Models\ApplicationStatusHistory;
use App\Models\User;

class ApplicationStatusHistoryPolicy
{
    public function view(User $user, ApplicationStatusHistory $history): bool
    {
        return $user->can('view', $history->application);
    }
}
