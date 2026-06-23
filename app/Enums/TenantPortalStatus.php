<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TenantPortalStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Blocked = 'blocked';
    case PendingActivation = 'pending_activation';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Blocked => 'Bloqueado',
            self::PendingActivation => 'Pendente de ativação',
            self::Archived => 'Arquivado',
        };
    }
}
