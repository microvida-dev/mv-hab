<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CandidateDataReuseProfileStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Active = 'active';
    case Outdated = 'outdated';
    case Expired = 'expired';
    case Superseded = 'superseded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Active => 'Ativo',
            self::Outdated => 'Desatualizado',
            self::Expired => 'Expirado',
            self::Superseded => 'Substituído',
            self::Cancelled => 'Cancelado',
        };
    }
}
