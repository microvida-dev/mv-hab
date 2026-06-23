<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DashboardType: string
{
    use HasOptions;

    case Operational = 'operational';
    case Executive = 'executive';
    case Financial = 'financial';
    case Maintenance = 'maintenance';
    case Administrative = 'administrative';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Operational => 'Operacional',
            self::Executive => 'Executivo',
            self::Financial => 'Financeiro',
            self::Maintenance => 'Manutenção',
            self::Administrative => 'Administrativo',
            self::Custom => 'Personalizado',
        };
    }
}
