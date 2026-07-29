<?php

namespace App\Services\Notifications;

use App\Enums\OfficialNotificationType;
use Illuminate\Support\Str;

final class ProceduralNotificationPolicy
{
    /**
     * Eventos processuais legacy que ainda não possuem correspondência no
     * enum OfficialNotificationType e não podem ser despromovidos por
     * configuração de templates.
     *
     * @var list<string>
     */
    private const MANDATORY_PROCEDURAL_EVENT_CODES = [
        'application_submitted',
    ];

    /**
     * Unknown official event codes fail closed as mandatory, except for
     * explicitly non-procedural namespaces.
     */
    public function requiresMandatoryEmail(
        string $eventCode,
        bool $official,
    ): bool {
        $type = OfficialNotificationType::tryFrom($eventCode);

        if ($type instanceof OfficialNotificationType) {
            return $type->requiresMandatoryEmail();
        }

        if (in_array(
            $eventCode,
            self::MANDATORY_PROCEDURAL_EVENT_CODES,
            true,
        )) {
            return true;
        }

        if (! $official) {
            return false;
        }

        return ! Str::startsWith($eventCode, [
            'public_visit_',
            'visit_',
            'support_ticket_',
            'marketing_',
            'commercial_',
        ]);
    }
}
