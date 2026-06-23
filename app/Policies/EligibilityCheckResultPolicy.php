<?php

namespace App\Policies;

use App\Models\EligibilityCheckResult;
use App\Models\User;

class EligibilityCheckResultPolicy
{
    public function view(User $user, EligibilityCheckResult $result): bool
    {
        return $user->can('view', $result->check);
    }
}
