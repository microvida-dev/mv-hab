<?php

namespace App\Policies;

use App\Models\NotificationEventRule;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;

class NotificationEventRulePolicy
{
    use ChecksCommunicationAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewCommunications($user);
    }

    public function view(User $user, NotificationEventRule $rule): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCommunications($user);
    }

    public function update(User $user, NotificationEventRule $rule): bool
    {
        return $this->canManageCommunications($user);
    }
}
