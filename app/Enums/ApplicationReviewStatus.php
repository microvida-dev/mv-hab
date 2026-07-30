<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationReviewStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case InProgress = 'in_progress';
    case ReadyForClosure = 'ready_for_closure';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::InProgress => 'Em curso',
            self::ReadyForClosure => 'Pronta para fecho',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }
}
