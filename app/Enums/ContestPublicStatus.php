<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContestPublicStatus: string
{
    use HasOptions;

    case Upcoming = 'upcoming';
    case Open = 'open';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Abertura futura',
            self::Open => 'Candidaturas abertas',
            self::Closed => 'Prazo encerrado',
            self::Archived => 'Arquivado',
        };
    }
}
