<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ConsentStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Active = 'active';
    case Withdrawn = 'withdrawn';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Active => 'Ativo',
            self::Withdrawn => 'Retirado',
            self::Expired => 'Expirado',
            self::Revoked => 'Revogado',
            self::Superseded => 'Substituído',
        };
    }
}
