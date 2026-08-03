<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContestApplicationPhase: string
{
    use HasOptions;

    case Cancelled = 'cancelled';
    case Upcoming = 'upcoming';
    case Applications = 'applications';
    case InitialReview = 'initial_review';
    case Corrections = 'corrections';
    case Revalidation = 'revalidation';
    case BetweenPhases = 'between_phases';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Cancelled => 'Concurso cancelado',
            self::Upcoming => 'Antes das candidaturas',
            self::Applications => 'Submissão de candidaturas',
            self::InitialReview => 'Análise inicial',
            self::Corrections => 'Aperfeiçoamento',
            self::Revalidation => 'Revalidação',
            self::BetweenPhases => 'Entre fases processuais',
            self::Completed => 'Fases processuais concluídas',
        };
    }

    public static function fromDeadlineType(ContestDeadlineType $type): ?self
    {
        return match ($type) {
            ContestDeadlineType::Applications => self::Applications,
            ContestDeadlineType::Review => self::InitialReview,
            ContestDeadlineType::Corrections => self::Corrections,
            ContestDeadlineType::Revalidation => self::Revalidation,
            default => null,
        };
    }
}
