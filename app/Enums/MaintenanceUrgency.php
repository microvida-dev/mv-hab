<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MaintenanceUrgency: string
{
    use HasOptions;

    case Low = 'low';
    case Normal = 'normal';
    case Urgent = 'urgent';
    case Emergency = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baixa',
            self::Normal => 'Normal',
            self::Urgent => 'Urgente',
            self::Emergency => 'Emergência',
        };
    }
}
