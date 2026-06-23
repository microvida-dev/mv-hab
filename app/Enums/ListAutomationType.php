<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ListAutomationType: string
{
    use HasOptions;

    case Provisional = 'provisional';
    case Definitive = 'definitive';
    case Final = 'final';
    case Admitted = 'admitted';
    case Excluded = 'excluded';
    case Ranked = 'ranked';
    case Allocated = 'allocated';

    public function label(): string
    {
        return match ($this) {
            self::Provisional => 'Provisória',
            self::Definitive => 'Definitiva',
            self::Final => 'Final',
            self::Admitted => 'Admitidos',
            self::Excluded => 'Excluídos',
            self::Ranked => 'Ordenados',
            self::Allocated => 'Atribuídos',
        };
    }
}
