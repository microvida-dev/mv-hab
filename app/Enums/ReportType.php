<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ReportType: string
{
    use HasOptions;

    case Operational = 'operational';
    case Executive = 'executive';
    case Sensitive = 'sensitive';
    case Audit = 'audit';
    case Export = 'export';

    public function label(): string
    {
        return match ($this) {
            self::Operational => 'Operacional',
            self::Executive => 'Executivo',
            self::Sensitive => 'Sensível',
            self::Audit => 'Auditoria',
            self::Export => 'Exportação',
        };
    }
}
