<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AuditEventSeverity: string
{
    use HasOptions;

    case Info = 'info';
    case Notice = 'notice';
    case Warning = 'warning';
    case Critical = 'critical';
    case Security = 'security';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Informação',
            self::Notice => 'Aviso',
            self::Warning => 'Alerta',
            self::Critical => 'Crítico',
            self::Security => 'Segurança',
        };
    }
}
