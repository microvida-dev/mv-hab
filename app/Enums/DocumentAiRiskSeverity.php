<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAiRiskSeverity: string
{
    use HasOptions;

    case Info = 'info';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Informativo',
            self::Low => 'Baixo',
            self::Medium => 'Médio',
            self::High => 'Alto',
            self::Critical => 'Crítico',
        };
    }
}
