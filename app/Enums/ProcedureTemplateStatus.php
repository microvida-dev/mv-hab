<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ProcedureTemplateStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Active => 'Ativa',
            self::Inactive => 'Inativa',
            self::Archived => 'Arquivada',
            self::Superseded => 'Substituída',
        };
    }
}
