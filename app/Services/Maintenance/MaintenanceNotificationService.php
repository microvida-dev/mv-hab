<?php

namespace App\Services\Maintenance;

use App\Enums\OfficialNotificationType;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Models\User;
use App\Services\Notifications\OfficialNotificationService;
use Throwable;

class MaintenanceNotificationService
{
    public function __construct(private readonly OfficialNotificationService $notifications) {}

    public function maintenanceStatus(MaintenanceRequest $request, OfficialNotificationType $type, string $subject, string $body, ?User $actor = null): void
    {
        $user = $request->requester;

        if (! $user) {
            return;
        }

        try {
            $this->notifications->createInternal($user, $type, $subject, $body, $request, $request->application, $actor);
        } catch (Throwable) {
            // Notifications are auxiliary; maintenance state must not fail because of an internal notice.
        }
    }

    public function inspectionStatus(PropertyInspection $inspection, OfficialNotificationType $type, string $subject, string $body, ?User $actor = null): void
    {
        $user = $inspection->leaseContract?->candidate;

        if (! $user) {
            return;
        }

        try {
            $this->notifications->createInternal($user, $type, $subject, $body, $inspection, $inspection->application, $actor);
        } catch (Throwable) {
            // Notifications are auxiliary; inspection state must not fail because of an internal notice.
        }
    }
}
