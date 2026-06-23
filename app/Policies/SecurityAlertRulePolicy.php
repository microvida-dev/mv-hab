<?php

namespace App\Policies;

use App\Models\SecurityAlertRule;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class SecurityAlertRulePolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return $this->backoffice($user, 'audit');
    }

    public function create(User $user): bool
    {
        return $this->backoffice($user, 'create');
    }

    public function update(User $user, SecurityAlertRule $rule): bool
    {
        return $this->backoffice($user, 'update');
    }
}
