<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAiExtractedFieldType: string
{
    use HasOptions;

    case String = 'string';
    case Date = 'date';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Money = 'money';
    case Percentage = 'percentage';
    case Identifier = 'identifier';
    case Address = 'address';
    case Enum = 'enum';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::String => 'Texto',
            self::Date => 'Data',
            self::Integer => 'Inteiro',
            self::Decimal => 'Decimal',
            self::Money => 'Valor monetário',
            self::Percentage => 'Percentagem',
            self::Identifier => 'Identificador',
            self::Address => 'Morada',
            self::Enum => 'Enumeração',
            self::Unknown => 'Desconhecido',
        };
    }
}
