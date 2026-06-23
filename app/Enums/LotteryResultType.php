<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LotteryResultType: string
{
    use HasOptions;

    case Selected = 'selected';
    case Reserve = 'reserve';
    case NotSelected = 'not_selected';
    case Excluded = 'excluded';

    public function label(): string
    {
        return match ($this) {
            self::Selected => 'Selecionado',
            self::Reserve => 'Suplente',
            self::NotSelected => 'Não selecionado',
            self::Excluded => 'Excluído',
        };
    }
}
