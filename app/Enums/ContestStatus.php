<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContestStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Published = 'published';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Published => 'Publicado',
            self::Cancelled => 'Cancelado',
            self::Archived => 'Arquivado',
        };
    }
}
