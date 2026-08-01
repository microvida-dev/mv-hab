<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CorrectionResponseKind: string
{
    use HasOptions;

    case Document = 'document';
    case Justification = 'justification';
    case Explanation = 'explanation';

    public function label(): string
    {
        return match ($this) {
            self::Document => 'Documento',
            self::Justification => 'Justificação',
            self::Explanation => 'Esclarecimento',
        };
    }
}
