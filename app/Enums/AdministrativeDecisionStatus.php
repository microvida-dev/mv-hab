<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AdministrativeDecisionStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Proposed = 'proposed';
    case Approved = 'approved';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Proposed => 'Proposta',
            self::Approved => 'Aprovada',
            self::Cancelled => 'Cancelada',
        };
    }
}
