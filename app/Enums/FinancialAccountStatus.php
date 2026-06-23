<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum FinancialAccountStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Suspended = 'suspended';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativa',
            self::Suspended => 'Suspensa',
            self::Closed => 'Fechada',
            self::Archived => 'Arquivada',
        };
    }
}
