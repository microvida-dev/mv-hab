<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RentScheduleStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Active = 'active';
    case Suspended = 'suspended';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Active => 'Ativo',
            self::Suspended => 'Suspenso',
            self::Closed => 'Fechado',
            self::Cancelled => 'Cancelado',
            self::Archived => 'Arquivado',
        };
    }
}
