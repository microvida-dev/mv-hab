<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ScoringOperator: string
{
    use HasOptions;

    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case GreaterThan = 'greater_than';
    case GreaterThanOrEqual = 'greater_than_or_equal';
    case LessThan = 'less_than';
    case LessThanOrEqual = 'less_than_or_equal';
    case Between = 'between';
    case IsTrue = 'is_true';
    case IsFalse = 'is_false';
    case Exists = 'exists';
    case NotExists = 'not_exists';
    case In = 'in';
    case NotIn = 'not_in';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Equals => 'Igual',
            self::NotEquals => 'Diferente',
            self::GreaterThan => 'Maior que',
            self::GreaterThanOrEqual => 'Maior ou igual',
            self::LessThan => 'Menor que',
            self::LessThanOrEqual => 'Menor ou igual',
            self::Between => 'Entre',
            self::IsTrue => 'Verdadeiro',
            self::IsFalse => 'Falso',
            self::Exists => 'Existe',
            self::NotExists => 'Não existe',
            self::In => 'Contido em',
            self::NotIn => 'Não contido em',
            self::Custom => 'Personalizado',
        };
    }
}
