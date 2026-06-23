<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationScoreStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Calculated = 'calculated';
    case RequiresManualReview = 'requires_manual_review';
    case ManualReviewCompleted = 'manual_review_completed';
    case ExcludedFromScoring = 'excluded_from_scoring';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Calculated => 'Calculada',
            self::RequiresManualReview => 'Requer avaliação manual',
            self::ManualReviewCompleted => 'Avaliação manual concluída',
            self::ExcludedFromScoring => 'Excluída da classificação',
            self::Locked => 'Bloqueada',
        };
    }
}
