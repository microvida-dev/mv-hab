<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RequiredDocumentConditionOperator: string
{
    use HasOptions;

    case Always = 'always';
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case GreaterThan = 'greater_than';
    case LessThan = 'less_than';
    case IsTrue = 'is_true';
    case IsFalse = 'is_false';
    case Exists = 'exists';

    public function label(): string
    {
        return match ($this) {
            self::Always => 'Sempre',
            self::Equals => 'Igual a',
            self::NotEquals => 'Diferente de',
            self::GreaterThan => 'Maior que',
            self::LessThan => 'Menor que',
            self::IsTrue => 'Verdadeiro',
            self::IsFalse => 'Falso',
            self::Exists => 'Preenchido',
        };
    }
}
