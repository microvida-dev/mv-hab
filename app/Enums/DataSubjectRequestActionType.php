<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DataSubjectRequestActionType: string
{
    use HasOptions;

    case IdentityVerification = 'identity_verification';
    case DataSearch = 'data_search';
    case DataExport = 'data_export';
    case Rectification = 'rectification';
    case Restriction = 'restriction';
    case ErasureReview = 'erasure_review';
    case Anonymization = 'anonymization';
    case ResponseSent = 'response_sent';
    case Closure = 'closure';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
