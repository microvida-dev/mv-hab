<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationReviewBatchStatus: string
{
    use HasOptions;

    case Sealed = 'sealed';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Sealed => 'Selado',
            self::Superseded => 'Substituído',
        };
    }
}
