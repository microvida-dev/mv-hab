<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TieBreakerDirection: string
{
    use HasOptions;

    case Asc = 'asc';
    case Desc = 'desc';

    public function label(): string
    {
        return match ($this) {
            self::Asc => 'Ascendente',
            self::Desc => 'Descendente',
        };
    }
}
