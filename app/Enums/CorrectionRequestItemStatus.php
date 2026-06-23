<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CorrectionRequestItemStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Responded = 'responded';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Waived = 'waived';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Responded => 'Respondido',
            self::Accepted => 'Aceite',
            self::Rejected => 'Rejeitado',
            self::Waived => 'Dispensado',
            self::Cancelled => 'Cancelado',
        };
    }
}
