<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MaintenanceCostType: string
{
    use HasOptions;

    case Labor = 'labor';
    case Materials = 'materials';
    case Travel = 'travel';
    case Inspection = 'inspection';
    case ExternalService = 'external_service';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Labor => 'Mão de obra',
            self::Materials => 'Materiais',
            self::Travel => 'Deslocação',
            self::Inspection => 'Vistoria',
            self::ExternalService => 'Serviço externo',
            self::Other => 'Outro',
        };
    }
}
