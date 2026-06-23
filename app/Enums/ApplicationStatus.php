<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case RequiresCorrection = 'requires_correction';
    case CorrectionSubmitted = 'correction_submitted';
    case Eligible = 'eligible';
    case Ineligible = 'ineligible';
    case Excluded = 'excluded';
    case Cancelled = 'cancelled';
    case Withdrawn = 'withdrawn';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Submitted => 'Submetida',
            self::UnderReview => 'Em análise',
            self::RequiresCorrection => 'Aguarda correção',
            self::CorrectionSubmitted => 'Correção submetida',
            self::Eligible => 'Elegível',
            self::Ineligible => 'Não elegível',
            self::Excluded => 'Excluída',
            self::Cancelled => 'Cancelada',
            self::Withdrawn => 'Desistida',
            self::Expired => 'Expirada',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::Draft,
            self::Submitted,
            self::UnderReview,
            self::RequiresCorrection,
            self::CorrectionSubmitted,
            self::Eligible,
        ], true);
    }

    public function canBeWithdrawn(): bool
    {
        return in_array($this, [self::Draft, self::Submitted], true);
    }
}
