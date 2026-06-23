<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RetentionAction: string
{
    use HasOptions;

    case Keep = 'keep';
    case Archive = 'archive';
    case Restrict = 'restrict';
    case Anonymize = 'anonymize';
    case Pseudonymize = 'pseudonymize';
    case DeleteCandidate = 'delete_candidate';
    case DeletePermanently = 'delete_permanently';
    case ReviewManually = 'review_manually';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
