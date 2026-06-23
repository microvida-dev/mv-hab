<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum SecurityAlertSeverity: string
{
    use HasOptions;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baixa',
            self::Medium => 'Média',
            self::High => 'Alta',
            self::Critical => 'Crítica',
        };
    }
}
