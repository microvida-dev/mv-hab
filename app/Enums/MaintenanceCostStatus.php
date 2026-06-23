<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MaintenanceCostStatus: string
{
    use HasOptions;

    case Estimated = 'estimated';
    case Approved = 'approved';
    case Incurred = 'incurred';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Estimated => 'Estimado',
            self::Approved => 'Aprovado',
            self::Incurred => 'Incorrido',
            self::Rejected => 'Rejeitado',
            self::Cancelled => 'Cancelado',
        };
    }
}
