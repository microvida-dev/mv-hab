<?php

namespace App\Policies;

use App\Models\NotificationTemplateVersion;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;

class NotificationTemplateVersionPolicy
{
    use ChecksCommunicationAccess;

    public function view(User $user, NotificationTemplateVersion $version): bool
    {
        return $this->canViewCommunications($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCommunications($user);
    }

    public function update(User $user, NotificationTemplateVersion $version): bool
    {
        return $this->canManageCommunications($user);
    }

    public function approve(User $user, NotificationTemplateVersion $version): bool
    {
        return $this->canPublishCommunications($user);
    }
}
