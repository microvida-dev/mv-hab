<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum VisitCancellationReason: string
{
    use HasOptions;

    case CandidateUnavailable = 'candidate_unavailable';
    case MunicipalServiceUnavailable = 'municipal_service_unavailable';
    case PropertyUnavailable = 'property_unavailable';
    case DuplicateBooking = 'duplicate_booking';
    case OperationalReason = 'operational_reason';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CandidateUnavailable => 'Indisponibilidade do candidato',
            self::MunicipalServiceUnavailable => 'Indisponibilidade dos serviços municipais',
            self::PropertyUnavailable => 'Imóvel indisponível',
            self::DuplicateBooking => 'Agendamento duplicado',
            self::OperationalReason => 'Motivo operacional',
            self::Other => 'Outro motivo',
        };
    }
}
