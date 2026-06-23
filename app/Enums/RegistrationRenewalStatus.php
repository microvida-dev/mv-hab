<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RegistrationRenewalStatus: string
{
    use HasOptions;

    case NotRequired = 'not_required';
    case Required = 'required';
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Completed = 'completed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Não necessária',
            self::Required => 'Necessária',
            self::Draft => 'Rascunho',
            self::InProgress => 'Em preenchimento',
            self::Submitted => 'Submetida',
            self::Completed => 'Concluída',
            self::Expired => 'Expirada',
            self::Cancelled => 'Cancelada',
        };
    }
}
