<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TicketCategory: string
{
    use HasOptions;

    case Application = 'application';
    case Documents = 'documents';
    case Eligibility = 'eligibility';
    case Legal = 'legal';
    case Contract = 'contract';
    case Payment = 'payment';
    case Maintenance = 'maintenance';
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
            self::Legal => 'Jurídico',
            self::Contract => 'Contrato',
            self::Payment => 'Pagamento',
            self::Maintenance => 'Manutenção',
            self::Visits => 'Visitas',
            self::TechnicalIssue => 'Problema técnico',
            self::ContestInformation => 'Informação sobre concurso',
            self::Notifications => 'Notificações',
            self::Rgpd => 'RGPD',
            self::Other => 'Outro',
        };
    }

    /**
     * @return list<string>
     */
    public function requiredBackofficeRoles(): array
    {
        return match ($this) {
            self::Eligibility, self::Documents => ['administrator', 'municipal_technician', 'jury', 'auditor'],
            self::Legal => ['administrator', 'legal_manager', 'jury', 'auditor'],
            self::Contract => ['administrator', 'legal_manager', 'housing_manager', 'auditor'],
            self::Payment => ['administrator', 'financial_manager', 'auditor'],
            self::Maintenance => ['administrator', 'maintenance_manager', 'auditor'],
            self::Rgpd => ['administrator', 'auditor'],
            default => [],
        };
    }
}
