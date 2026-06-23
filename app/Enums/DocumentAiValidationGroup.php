<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAiValidationGroup: string
{
    use HasOptions;

    case Identification = 'identificacao';
    case Household = 'agregado';
    case Income = 'rendimentos';
    case Housing = 'habitacao';
    case Document = 'documento';

    public function label(): string
    {
        return match ($this) {
            self::Identification => 'Identificação',
            self::Household => 'Agregado',
            self::Income => 'Rendimentos',
            self::Housing => 'Habitação',
            self::Document => 'Documento',
        };
    }
}
