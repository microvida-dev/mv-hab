<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ReportExportStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Processing => 'Em processamento',
            self::Completed => 'Concluída',
            self::Failed => 'Falhou',
            self::Cancelled => 'Cancelada',
            self::Expired => 'Expirada',
        };
    }
}
