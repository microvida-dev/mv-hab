<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DataSubjectRequestStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case Received = 'received';
    case IdentityVerification = 'identity_verification';
    case UnderReview = 'under_review';
    case RequiresInformation = 'requires_information';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Closed = 'closed';
    case Overdue = 'overdue';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
