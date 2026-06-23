<?php

namespace App\Services\Notifications;

use App\Enums\OfficialNotificationStatus;
use App\Models\OfficialNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class NotificationCenterService
{
    /** @return Builder<OfficialNotification> */
    public function forUser(User $user): Builder
    {
        return OfficialNotification::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', [OfficialNotificationStatus::Draft->value, OfficialNotificationStatus::Cancelled->value])
            ->latest();
    }

    public function unreadCount(User $user): int
    {
        return $this->forUser($user)->whereNull('read_at')->count();
    }
}
