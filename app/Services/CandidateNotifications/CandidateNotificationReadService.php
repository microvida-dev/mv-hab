<?php

namespace App\Services\CandidateNotifications;

use App\Enums\TimelineEventType;
use App\Enums\TimelineEventVisibility;
use App\Models\OfficialNotification;
use App\Models\User;
use App\Services\Notifications\OfficialNotificationService;
use App\Services\ProcessTracking\ProcessTimelineService;

class CandidateNotificationReadService
{
    public function __construct(
        private readonly OfficialNotificationService $notifications,
        private readonly ProcessTimelineService $timeline,
    ) {}

    public function markRead(OfficialNotification $notification, User $actor): OfficialNotification
    {
        $notification = $this->notifications->markRead($notification, $actor);
        if ($notification->application !== null) {
            $this->timeline->record(
                application: $notification->application,
                type: TimelineEventType::NotificationRead,
                visibility: TimelineEventVisibility::CandidateVisible,
                title: 'Notificação lida',
                description: $notification->subject,
                actor: $actor,
                related: $notification,
            );
        }

        return $notification;
    }

    public function archive(OfficialNotification $notification, User $actor): OfficialNotification
    {
        return $this->notifications->archive($notification, $actor);
    }
}
