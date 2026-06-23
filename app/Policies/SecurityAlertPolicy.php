<?php

namespace App\Policies;

use App\Models\SecurityAlert;
use App\Models\User;
use App\Policies\Concerns\HandlesSecurityAccess;

class SecurityAlertPolicy
{
    use HandlesSecurityAccess;

    public function viewAny(User $user): bool
    {
        return $this->backoffice($user, 'audit');
    }

    public function view(User $user, SecurityAlert $alert): bool
    {
        return $this->backoffice($user, 'audit');
    }

    public function update(User $user, SecurityAlert $alert): bool
    {
        return $this->backoffice($user, 'audit');
    }
}
