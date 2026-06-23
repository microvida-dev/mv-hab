<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum SimulationSessionStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Saved = 'saved';
    case ConvertedToRegistration = 'converted_to_registration';
    case ConvertedToApplicationDraft = 'converted_to_application_draft';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::InProgress => 'Em preenchimento',
            self::Completed => 'Concluída',
            self::Saved => 'Guardada',
            self::ConvertedToRegistration => 'Convertida em registo',
            self::ConvertedToApplicationDraft => 'Convertida em rascunho',
            self::Expired => 'Expirada',
            self::Cancelled => 'Cancelada',
        };
    }
}
