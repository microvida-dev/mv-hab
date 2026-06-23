<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MaintenancePriority: string
{
    use HasOptions;

    case Low = 'low';
    case Normal = 'normal';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baixa',
            self::Normal => 'Normal',
            self::Medium => 'Média',
            self::High => 'Alta',
            self::Urgent => 'Urgente',
        };
    }
}
