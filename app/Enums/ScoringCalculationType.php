<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ScoringCalculationType: string
{
    use HasOptions;

    case FixedPoints = 'fixed_points';
    case Boolean = 'boolean';
    case Range = 'range';
    case Threshold = 'threshold';
    case Proportional = 'proportional';
    case Weighted = 'weighted';
    case Manual = 'manual';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::FixedPoints => 'Pontos fixos',
            self::Boolean => 'Booleano',
            self::Range => 'Intervalo',
            self::Threshold => 'Limite',
            self::Proportional => 'Proporcional',
            self::Weighted => 'Ponderado',
            self::Manual => 'Manual',
            self::Custom => 'Personalizado',
        };
    }
}
