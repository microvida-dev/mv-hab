<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PaymentImportRowStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Matched = 'matched';
    case Unmatched = 'unmatched';
    case Imported = 'imported';
    case Failed = 'failed';
    case Ignored = 'ignored';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Matched => 'Correspondida',
            self::Unmatched => 'Sem correspondência',
            self::Imported => 'Importada',
            self::Failed => 'Falhada',
            self::Ignored => 'Ignorada',
        };
    }
}
