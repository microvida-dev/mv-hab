<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum SecurityChecklistStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Passed = 'passed';
    case Failed = 'failed';
    case PartiallyPassed = 'partially_passed';
    case Approved = 'approved';
    case Archived = 'archived';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
