<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MessageVisibility: string
{
    use HasOptions;

    case CandidateVisible = 'candidate_visible';
    case InternalOnly = 'internal_only';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::CandidateVisible => 'Visível ao candidato',
            self::InternalOnly => 'Apenas interno',
            self::System => 'Sistema',
        };
    }
}
