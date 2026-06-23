<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum HousingCondition: string
{
    use HasOptions;

    case Adequate = 'adequate';
    case Overcrowded = 'overcrowded';
    case Degraded = 'degraded';
    case Unsafe = 'unsafe';
    case Inaccessible = 'inaccessible';
    case Temporary = 'temporary';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Adequate => 'Adequada',
            self::Overcrowded => 'Sobreocupada',
            self::Degraded => 'Degradada',
            self::Unsafe => 'Sem condições de segurança',
            self::Inaccessible => 'Sem acessibilidade adequada',
            self::Temporary => 'Temporária',
            self::Other => 'Outra condição',
        };
    }
}
