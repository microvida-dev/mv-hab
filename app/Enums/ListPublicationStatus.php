<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ListPublicationStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Published = 'published';
    case Unpublished = 'unpublished';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Published => 'Publicada',
            self::Unpublished => 'Retirada',
            self::Expired => 'Expirada',
            self::Cancelled => 'Cancelada',
        };
    }
}
