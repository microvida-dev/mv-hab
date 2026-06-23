<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InspectionCondition: string
{
    use HasOptions;

    case Good = 'good';
    case Acceptable = 'acceptable';
    case RequiresRepair = 'requires_repair';
    case Poor = 'poor';
    case Critical = 'critical';
    case NotApplicable = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Boa',
            self::Acceptable => 'Aceitável',
            self::RequiresRepair => 'Requer reparação',
            self::Poor => 'Má',
            self::Critical => 'Crítica',
            self::NotApplicable => 'Não aplicável',
        };
    }
}
