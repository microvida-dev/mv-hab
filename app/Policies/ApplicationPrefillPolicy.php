<?php

namespace App\Policies;

use App\Models\ApplicationPrefill;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ApplicationPrefillPolicy
{
    use ChecksPermissions;

    public function view(User $user, ApplicationPrefill $applicationPrefill): bool
    {
        return $applicationPrefill->user_id === $user->id
            && $this->canAccess($user, 'simulator', 'view');
    }

    public function update(User $user, ApplicationPrefill $applicationPrefill): bool
    {
        return $applicationPrefill->user_id === $user->id
            && $this->canAccess($user, 'simulator', 'update');
    }
}
