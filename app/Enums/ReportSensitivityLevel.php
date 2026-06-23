<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ReportSensitivityLevel: string
{
    use HasOptions;

    case PublicInternal = 'public_internal';
    case Restricted = 'restricted';
    case Sensitive = 'sensitive';
    case HighlySensitive = 'highly_sensitive';

    public function label(): string
    {
        return match ($this) {
            self::PublicInternal => 'Interno',
            self::Restricted => 'Restrito',
            self::Sensitive => 'Sensível',
            self::HighlySensitive => 'Altamente sensível',
        };
    }

    public function requiresConfirmation(): bool
    {
        return in_array($this, [self::Sensitive, self::HighlySensitive], true);
    }
}
