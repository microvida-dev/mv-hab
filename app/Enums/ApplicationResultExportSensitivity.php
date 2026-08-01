<?php

namespace App\Enums;

enum ApplicationResultExportSensitivity: string
{
    case Operational = 'operational';
    case ProcessReference = 'process_reference';
    case Personal = 'personal';
    case HighlySensitive = 'highly_sensitive';
    case Internal = 'internal';

    public function includedByDefault(): bool
    {
        return in_array($this, [
            self::Operational,
            self::ProcessReference,
        ], true);
    }

    public function canBeIncludedInSensitiveExport(): bool
    {
        return $this !== self::HighlySensitive
            && $this !== self::Internal;
    }
}
