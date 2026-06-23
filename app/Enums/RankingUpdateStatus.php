<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RankingUpdateStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Applied = 'applied';
    case Reviewed = 'reviewed';
    case Approved = 'approved';
    case Reverted = 'reverted';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Applied => 'Aplicado',
            self::Reviewed => 'Revisto',
            self::Approved => 'Aprovado',
            self::Reverted => 'Revertido',
            self::Failed => 'Falhado',
        };
    }
}
