<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LegalRegimeResolutionStatus: string
{
    use HasOptions;

    case Resolved = 'resolved';
    case RequiresManualReview = 'requires_manual_review';

    public function label(): string
    {
        return match ($this) {
            self::Resolved => 'Resolvido',
            self::RequiresManualReview => 'Requer revisão manual',
        };
    }
}
