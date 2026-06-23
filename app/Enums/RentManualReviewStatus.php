<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RentManualReviewStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Approved => 'Aprovada',
            self::Rejected => 'Rejeitada',
            self::Cancelled => 'Cancelada',
        };
    }
}
