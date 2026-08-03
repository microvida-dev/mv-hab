<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PublicVisitBookingStatus: string
{
    use HasOptions;

    case Booked = 'booked';
    case Cancelled = 'cancelled';
    case Attended = 'attended';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Marcada',
            self::Cancelled => 'Cancelada',
            self::Attended => 'Realizada',
            self::NoShow => 'Falta de comparência',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Booked;
    }
}
