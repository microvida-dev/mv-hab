<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationReviewBatchOutcome: string
{
    use HasOptions;

    case CompletePendingDecision = 'complete_pending_decision';
    case CorrectionRequired = 'correction_required';
    case CorrectionRejected = 'correction_rejected';
    case Withdrawn = 'withdrawn';
    case NotAssessed = 'not_assessed';

    public function label(): string
    {
        return match ($this) {
            self::CompletePendingDecision => 'Completa, a aguardar decisão',
            self::CorrectionRequired => 'Requer aperfeiçoamento',
            self::CorrectionRejected => 'Aperfeiçoamento não aceite',
            self::Withdrawn => 'Desistência registada',
            self::NotAssessed => 'Não avaliada',
        };
    }
}
