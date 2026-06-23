<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum NotificationCenterStatus: string
{
    use HasOptions;

    case Unread = 'unread';
    case Read = 'read';
    case Archived = 'archived';
    case Expired = 'expired';
    case ActionRequired = 'action_required';

    public function label(): string
    {
        return match ($this) {
            self::Unread => 'Não lida',
            self::Read => 'Lida',
            self::Archived => 'Arquivada',
            self::Expired => 'Expirada',
            self::ActionRequired => 'Ação obrigatória',
        };
    }
}
