<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AdditionalDocumentStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case RequiresReplacement = 'requires_replacement';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Submitted => 'Submetido',
            self::UnderReview => 'Em análise',
            self::Accepted => 'Aceite',
            self::Rejected => 'Rejeitado',
            self::RequiresReplacement => 'Requer substituição',
            self::Cancelled => 'Cancelado',
        };
    }
}
