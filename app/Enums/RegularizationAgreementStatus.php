<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RegularizationAgreementStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Proposed = 'proposed';
    case Active = 'active';
    case Completed = 'completed';
    case Breached = 'breached';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Proposed => 'Proposto',
            self::Active => 'Ativo',
            self::Completed => 'Concluído',
            self::Breached => 'Incumprido',
            self::Cancelled => 'Cancelado',
            self::Archived => 'Arquivado',
        };
    }
}
