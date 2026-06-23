<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TypologyRecommendationStatus: string
{
    use HasOptions;

    case Recommended = 'recommended';
    case Possible = 'possible';
    case NotRecommended = 'not_recommended';
    case RequiresReview = 'requires_review';
    case InsufficientData = 'insufficient_data';

    public function label(): string
    {
        return match ($this) {
            self::Recommended => 'Recomendada',
            self::Possible => 'Possível',
            self::NotRecommended => 'Não recomendada',
            self::RequiresReview => 'Requer análise',
            self::InsufficientData => 'Dados insuficientes',
        };
    }
}
