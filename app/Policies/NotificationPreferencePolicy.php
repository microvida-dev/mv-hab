<?php

namespace App\Policies;

use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPreferencePolicy
{
    public function view(User $user, NotificationPreference $preference): bool
    {
        return $preference->user_id === $user->id || $user->hasPermissionTo('notifications', 'view');
    }

    public function update(User $user, NotificationPreference $preference): bool
    {
        return $preference->user_id === $user->id || (! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('notifications', 'update'));
    }
}
