<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ProcessActionStatus: string
{
    use HasOptions;

    case Available = 'available';
    case Completed = 'completed';
    case Blocked = 'blocked';
    case Expired = 'expired';
    case NotApplicable = 'not_applicable';
    case PendingReview = 'pending_review';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponível',
            self::Completed => 'Concluída',
            self::Blocked => 'Bloqueada',
            self::Expired => 'Expirada',
            self::NotApplicable => 'Não aplicável',
            self::PendingReview => 'Em análise',
        };
    }
}
