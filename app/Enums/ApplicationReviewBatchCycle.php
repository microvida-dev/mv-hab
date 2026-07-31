<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationReviewBatchCycle: string
{
    use HasOptions;

    case InitialReview = 'initial_review';
    case Revalidation = 'revalidation';

    public function label(): string
    {
        return match ($this) {
            self::InitialReview => 'Validação inicial',
            self::Revalidation => 'Revalidação após aperfeiçoamento',
        };
    }
}
