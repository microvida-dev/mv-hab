<?php

namespace App\Policies;

use App\Models\OfficialNotification;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class OfficialNotificationPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'notifications', 'view');
    }

    public function view(User $user, OfficialNotification $notification): bool
    {
        return $user->hasRole('candidate')
            ? $notification->user_id === $user->id && $this->canAccess($user, 'notifications', 'view')
            : $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'notifications', 'create');
    }

    public function update(User $user, OfficialNotification $notification): bool
    {
        return $notification->user_id === $user->id || (! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'notifications', 'update'));
    }

    public function acknowledge(User $user, OfficialNotification $notification): bool
    {
        return $notification->user_id === $user->id && $notification->requires_acknowledgement;
    }

    public function archive(User $user, OfficialNotification $notification): bool
    {
        return $notification->user_id === $user->id || (! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'notifications', 'update'));
    }
}
