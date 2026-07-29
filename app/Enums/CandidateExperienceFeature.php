<?php

namespace App\Enums;

enum CandidateExperienceFeature: string
{
    case NotificationPreferences = 'notification_preferences';
    case LegacyVisits = 'legacy_visits';
}
