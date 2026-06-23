<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TemplateStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Active => 'Ativo',
            self::Inactive => 'Inativo',
            self::Archived => 'Arquivado',
        };
    }
}
