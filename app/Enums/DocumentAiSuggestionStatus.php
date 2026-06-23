<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAiSuggestionStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Accepted = 'accepted';
    case Edited = 'edited';
    case Dismissed = 'dismissed';
    case Sent = 'sent';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Accepted => 'Aceite',
            self::Edited => 'Editada',
            self::Dismissed => 'Descartada',
            self::Sent => 'Enviada',
        };
    }
}
