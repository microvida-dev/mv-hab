<?php

namespace App\Policies;

use App\Models\NotificationTemplate;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;

class NotificationTemplatePolicy
{
    use ChecksCommunicationAccess;

    public function viewAny(User $user): bool
    {
        return $this->canViewCommunications($user);
    }

    public function view(User $user, NotificationTemplate $template): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCommunications($user);
    }

    public function update(User $user, NotificationTemplate $template): bool
    {
        return $this->canManageCommunications($user);
    }

    public function approve(User $user, NotificationTemplate $template): bool
    {
        return $this->canPublishCommunications($user);
    }
}
