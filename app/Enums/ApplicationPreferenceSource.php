<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationPreferenceSource: string
{
    use HasOptions;

    case Uninitialized = 'uninitialized';
    case Legacy = 'legacy';
    case Official = 'official';
    case Reconciled = 'reconciled';
    case RequiresManualReview = 'requires_manual_review';

    public function label(): string
    {
        return match ($this) {
            self::Uninitialized => 'Não inicializada',
            self::Legacy => 'Legacy',
            self::Official => 'Oficial',
            self::Reconciled => 'Reconciliada',
            self::RequiresManualReview => 'Requer revisão manual',
        };
    }

    public function isOfficial(): bool
    {
        return in_array($this, [self::Official, self::Reconciled], true);
    }
}
