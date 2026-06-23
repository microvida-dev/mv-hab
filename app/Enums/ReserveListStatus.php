<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ReserveListStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Active = 'active';
    case Locked = 'locked';
    case Archived = 'archived';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Active => 'Ativa',
            self::Locked => 'Bloqueada',
            self::Archived => 'Arquivada',
            self::Cancelled => 'Cancelada',
        };
    }
}
