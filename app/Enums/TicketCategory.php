<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TicketCategory: string
{
    use HasOptions;

    case Application = 'application';
    case Documents = 'documents';
    case Eligibility = 'eligibility';
    case Visits = 'visits';
    case TechnicalIssue = 'technical_issue';
    case ContestInformation = 'contest_information';
    case Notifications = 'notifications';
    case Rgpd = 'rgpd';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Application => 'Candidatura',
            self::Documents => 'Documentos',
            self::Eligibility => 'Elegibilidade',
            self::Visits => 'Visitas',
            self::TechnicalIssue => 'Problema técnico',
            self::ContestInformation => 'Informação sobre concurso',
            self::Notifications => 'Notificações',
            self::Rgpd => 'RGPD',
            self::Other => 'Outro',
        };
    }
}
