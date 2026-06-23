<?php

namespace App\Policies;

use App\Models\OfficialNotification;
use App\Models\User;

class CandidateNotificationPolicy
{
    public function view(User $user, OfficialNotification $notification): bool
    {
        return $notification->user_id === $user->id || $user->hasPermissionTo('notifications', 'view');
    }

    public function update(User $user, OfficialNotification $notification): bool
    {
        return $notification->user_id === $user->id || $user->hasPermissionTo('notifications', 'update');
    }

    public function archive(User $user, OfficialNotification $notification): bool
    {
        return $this->update($user, $notification);
    }
}
