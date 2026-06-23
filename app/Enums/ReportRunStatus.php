<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ReportRunStatus: string
{
    use HasOptions;

    case Started = 'started';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Started => 'Iniciada',
            self::Completed => 'Concluída',
            self::Failed => 'Falhou',
            self::Cancelled => 'Cancelada',
        };
    }
}
