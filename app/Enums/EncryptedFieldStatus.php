<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EncryptedFieldStatus: string
{
    use HasOptions;

    case NotEncrypted = 'not_encrypted';
    case Planned = 'planned';
    case Encrypted = 'encrypted';
    case HashIndexed = 'hash_indexed';
    case NotApplicable = 'not_applicable';
    case BlockedBySearchRequirement = 'blocked_by_search_requirement';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
