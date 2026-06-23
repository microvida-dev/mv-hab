<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ListEntryType: string
{
    use HasOptions;

    case Admitted = 'admitted';
    case Excluded = 'excluded';
    case Ranked = 'ranked';

    public function label(): string
    {
        return match ($this) {
            self::Admitted => 'Admitida',
            self::Excluded => 'Excluída',
            self::Ranked => 'Classificada',
        };
    }
}
