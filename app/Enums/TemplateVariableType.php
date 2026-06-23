<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TemplateVariableType: string
{
    use HasOptions;

    case String = 'string';
    case Number = 'number';
    case Date = 'date';
    case DateTime = 'datetime';
    case Currency = 'currency';
    case Boolean = 'boolean';
    case Url = 'url';
    case Html = 'html';
    case PlainText = 'plain_text';

    public function label(): string
    {
        return match ($this) {
            self::String => 'Texto',
            self::Number => 'Número',
            self::Date => 'Data',
            self::DateTime => 'Data e hora',
            self::Currency => 'Moeda',
            self::Boolean => 'Booleano',
            self::Url => 'URL',
            self::Html => 'HTML',
            self::PlainText => 'Texto simples',
        };
    }
}
