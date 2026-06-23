<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CommunicationDeliveryStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Queued = 'queued';
    case Processing = 'processing';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Bounced = 'bounced';
    case Cancelled = 'cancelled';
    case Disabled = 'disabled';
    case Simulated = 'simulated';
    case PendingConfiguration = 'pending_configuration';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Queued => 'Em fila',
            self::Processing => 'Em processamento',
            self::Sent => 'Enviada',
            self::Delivered => 'Entregue',
            self::Failed => 'Falhada',
            self::Bounced => 'Devolvida',
            self::Cancelled => 'Cancelada',
            self::Disabled => 'Desativada',
            self::Simulated => 'Simulada',
            self::PendingConfiguration => 'Aguarda configuração',
        };
    }
}
