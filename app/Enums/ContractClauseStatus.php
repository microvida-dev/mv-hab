<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContractClauseStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Active => 'Ativa',
            self::Archived => 'Arquivada',
        };
    }
}
