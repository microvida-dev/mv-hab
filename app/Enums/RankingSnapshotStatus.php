<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RankingSnapshotStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Internal = 'internal';
    case Locked = 'locked';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Internal => 'Interno',
            self::Locked => 'Bloqueado',
            self::Archived => 'Arquivado',
        };
    }
}
