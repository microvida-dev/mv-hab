<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAiOcrStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Unavailable = 'unavailable';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Processing => 'Em processamento',
            self::Completed => 'Concluído',
            self::Failed => 'Falhado',
            self::Unavailable => 'Indisponível',
            self::Skipped => 'Ignorado',
        };
    }
}
