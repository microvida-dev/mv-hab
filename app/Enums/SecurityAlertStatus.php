<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum SecurityAlertStatus: string
{
    use HasOptions;

    case Open = 'open';
    case UnderReview = 'under_review';
    case Confirmed = 'confirmed';
    case FalsePositive = 'false_positive';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
