<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum BackupReviewStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Reviewed = 'reviewed';
    case RequiresAction = 'requires_action';
    case Approved = 'approved';
    case Archived = 'archived';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
