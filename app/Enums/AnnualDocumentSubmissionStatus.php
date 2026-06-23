<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AnnualDocumentSubmissionStatus: string
{
    use HasOptions;

    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submetida',
            self::Accepted => 'Aceite',
            self::Rejected => 'Rejeitada',
            self::Cancelled => 'Cancelada',
        };
    }
}
