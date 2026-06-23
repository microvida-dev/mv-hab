<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum NotificationPriority: string
{
    use HasOptions;

    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baixa',
            self::Normal => 'Normal',
            self::High => 'Alta',
            self::Urgent => 'Urgente',
            self::Critical => 'Crítica',
        };
    }
}
