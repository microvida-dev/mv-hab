<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PaymentImportStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Processing = 'processing';
    case Processed = 'processed';
    case PartiallyProcessed = 'partially_processed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Processing => 'Em processamento',
            self::Processed => 'Processado',
            self::PartiallyProcessed => 'Parcialmente processado',
            self::Failed => 'Falhado',
            self::Cancelled => 'Cancelado',
            self::Reversed => 'Revertido',
        };
    }
}
