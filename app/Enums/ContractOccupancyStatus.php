<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContractOccupancyStatus: string
{
    use HasOptions;

    case Active = 'active';
    case PendingStart = 'pending_start';
    case Suspended = 'suspended';
    case Terminated = 'terminated';
    case Expired = 'expired';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativa',
            self::PendingStart => 'A aguardar início',
            self::Suspended => 'Suspensa',
            self::Terminated => 'Terminada',
            self::Expired => 'Expirada',
            self::Archived => 'Arquivada',
        };
    }
}
