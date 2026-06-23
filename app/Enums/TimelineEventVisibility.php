<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TimelineEventVisibility: string
{
    use HasOptions;

    case CandidateVisible = 'candidate_visible';
    case BackofficeOnly = 'backoffice_only';
    case AuditorVisible = 'auditor_visible';
    case SystemOnly = 'system_only';

    public function label(): string
    {
        return match ($this) {
            self::CandidateVisible => 'Visível ao candidato',
            self::BackofficeOnly => 'Apenas backoffice',
            self::AuditorVisible => 'Auditoria',
            self::SystemOnly => 'Sistema',
        };
    }
}
