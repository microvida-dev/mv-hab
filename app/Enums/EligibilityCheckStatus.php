<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EligibilityCheckStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Em preparação',
            self::Completed => 'Concluída',
            self::Failed => 'Falhou',
            self::Cancelled => 'Cancelada',
        };
    }
}
