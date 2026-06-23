<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CommunicationAttemptStatus: string
{
    use HasOptions;

    case Started = 'started';
    case Success = 'success';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Started => 'Iniciada',
            self::Success => 'Concluída',
            self::Failed => 'Falhada',
            self::Cancelled => 'Cancelada',
            self::Skipped => 'Ignorada',
        };
    }
}
