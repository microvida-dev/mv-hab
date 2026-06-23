<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LeasePaymentStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case PartiallyAllocated = 'partially_allocated';
    case Allocated = 'allocated';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Pending => 'Pendente',
            self::Confirmed => 'Confirmado',
            self::PartiallyAllocated => 'Parcialmente imputado',
            self::Allocated => 'Imputado',
            self::Reversed => 'Estornado',
            self::Cancelled => 'Cancelado',
            self::Failed => 'Falhado',
        };
    }
}
