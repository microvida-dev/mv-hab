<?php

namespace App\Services\CandidateNotifications;

use App\Enums\NotificationCenterStatus;
use App\Enums\OfficialNotificationStatus;
use App\Models\OfficialNotification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CandidateNotificationCenterService
{
    /**
     * @return Builder<OfficialNotification>
     */
    public function queryFor(User $user): Builder
    {
        return OfficialNotification::query()
            ->where('user_id', $user->id)
            ->latest();
    }

    /**
     * @return LengthAwarePaginator<int, OfficialNotification>
     */
    public function paginateFor(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->queryFor($user)->paginate($perPage);
    }

    /**
     * @return array{unread: int, read: int, archived: int, expired: int, action_required: int}
     */
    public function counts(User $user): array
    {
        return [
            'unread' => (clone $this->queryFor($user))->whereIn('status', [
                OfficialNotificationStatus::Queued->value,
                OfficialNotificationStatus::Published->value,
                OfficialNotificationStatus::Sent->value,
                OfficialNotificationStatus::Delivered->value,
            ])->count(),
            'read' => (clone $this->queryFor($user))->where('status', OfficialNotificationStatus::Read->value)->count(),
            'archived' => (clone $this->queryFor($user))->where('status', OfficialNotificationStatus::Archived->value)->count(),
            'expired' => (clone $this->queryFor($user))->where('status', OfficialNotificationStatus::Expired->value)->count(),
            'action_required' => (clone $this->queryFor($user))->where('requires_acknowledgement', true)->whereNull('acknowledged_at')->count(),
        ];
    }

    public function centerStatusFor(OfficialNotification $notification): NotificationCenterStatus
    {
        if ($notification->status === OfficialNotificationStatus::Archived) {
            return NotificationCenterStatus::Archived;
        }

        if ($notification->status === OfficialNotificationStatus::Expired) {
            return NotificationCenterStatus::Expired;
        }

        if ($notification->requires_acknowledgement && $notification->acknowledged_at === null) {
            return NotificationCenterStatus::ActionRequired;
        }

        return $notification->read_at === null ? NotificationCenterStatus::Unread : NotificationCenterStatus::Read;
    }
}
