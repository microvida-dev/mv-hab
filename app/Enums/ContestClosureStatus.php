<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContestClosureStatus: string
{
    use HasOptions;

    case Open = 'open';
    case PendingDraw = 'pending_draw';
    case DrawCompleted = 'draw_completed';
    case AllocationCompleted = 'allocation_completed';
    case KeysPending = 'keys_pending';
    case TenantTransitionPending = 'tenant_transition_pending';
    case Closed = 'closed';
    case Archived = 'archived';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberto',
            self::PendingDraw => 'Sorteio pendente',
            self::DrawCompleted => 'Sorteio concluído',
            self::AllocationCompleted => 'Atribuição concluída',
            self::KeysPending => 'Entrega de chaves pendente',
            self::TenantTransitionPending => 'Transição para inquilino pendente',
            self::Closed => 'Fechado',
            self::Archived => 'Arquivado',
            self::Cancelled => 'Cancelado',
        };
    }
}
