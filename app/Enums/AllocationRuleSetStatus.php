<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AllocationRuleSetStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Active => 'Ativo',
            self::Archived => 'Arquivado',
        };
    }
}
