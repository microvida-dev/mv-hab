<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CommunicationStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Queued = 'queued';
    case Processing = 'processing';
    case Sent = 'sent';
    case PartiallySent = 'partially_sent';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Queued => 'Em fila',
            self::Processing => 'Em processamento',
            self::Sent => 'Enviada',
            self::PartiallySent => 'Parcialmente enviada',
            self::Failed => 'Falhada',
            self::Cancelled => 'Cancelada',
            self::Archived => 'Arquivada',
        };
    }
}
