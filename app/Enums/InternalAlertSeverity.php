<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InternalAlertSeverity: string
{
    use HasOptions;

    case Info = 'info';
    case Warning = 'warning';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Informativo',
            self::Warning => 'Aviso',
            self::High => 'Elevado',
            self::Critical => 'Crítico',
        };
    }
}
