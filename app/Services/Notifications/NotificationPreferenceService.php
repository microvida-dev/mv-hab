<?php

namespace App\Services\Notifications;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class NotificationPreferenceService
{
    public function for(User $user): NotificationPreference
    {
        $preference = $user->notificationPreference()->firstOrCreate([], [
            'allow_in_app' => true,
            'allow_email' => true,
            'allow_sms' => false,
            'allow_postal' => true,
            'email_for_notifications' => $user->email,
        ]);

        return $preference;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, AuditLogger $audit): NotificationPreference
    {
        $preference = $this->for($user);
        $preference->fill($data);
        $preference->forceFill([
            'allow_in_app' => true,
            'consented_at' => now(),
            'revoked_at' => empty($data['allow_email']) && empty($data['allow_sms']) ? now() : null,
        ])->save();
        $audit->record(AuditEvents::UPDATE, $preference, 'notifications', 'notification_preferences_updated', 'Preferências de notificação atualizadas.');

        return $preference->refresh();
    }
}
