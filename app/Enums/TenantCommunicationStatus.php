<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TenantCommunicationStatus: string
{
    use HasOptions;

    case Open = 'open';
    case AwaitingTenant = 'awaiting_tenant';
    case AwaitingMunicipality = 'awaiting_municipality';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberta',
            self::AwaitingTenant => 'A aguardar inquilino',
            self::AwaitingMunicipality => 'A aguardar município',
            self::Closed => 'Fechada',
            self::Archived => 'Arquivada',
        };
    }
}
