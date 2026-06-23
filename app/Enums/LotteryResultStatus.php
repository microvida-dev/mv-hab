<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LotteryResultStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Generated = 'generated';
    case Validated = 'validated';
    case Approved = 'approved';
    case Cancelled = 'cancelled';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Generated => 'Gerado',
            self::Validated => 'Validado',
            self::Approved => 'Aprovado',
            self::Cancelled => 'Cancelado',
            self::Superseded => 'Substituído',
        };
    }
}
