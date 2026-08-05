<?php

declare(strict_types=1);

namespace App\Enums;

enum ActorProfile: string
{
    case PlatformAdministrator = 'platform_administrator';
    case MunicipalAdministrator = 'municipal_administrator';
    case MunicipalTechnician = 'municipal_technician';
    case Jury = 'jury';
    case LegalManager = 'legal_manager';
    case FinancialManager = 'financial_manager';
    case HousingManager = 'housing_manager';
    case MaintenanceManager = 'maintenance_manager';
    case InspectionManager = 'inspection_manager';
    case SupportAgent = 'support_agent';
    case Auditor = 'auditor';
    case Candidate = 'candidate';
    case Unclassified = 'unclassified';

    public function label(): string
    {
        return match ($this) {
            self::PlatformAdministrator => 'Administração da plataforma',
            self::MunicipalAdministrator => 'Administração municipal',
            self::MunicipalTechnician => 'Técnico municipal',
            self::Jury => 'Júri',
            self::LegalManager => 'Gestão jurídica',
            self::FinancialManager => 'Gestão financeira',
            self::HousingManager => 'Gestão habitacional',
            self::MaintenanceManager => 'Manutenção',
            self::InspectionManager => 'Vistorias',
            self::SupportAgent => 'Atendimento',
            self::Auditor => 'Auditoria',
            self::Candidate => 'Candidato',
            self::Unclassified => 'Acesso não classificado',
        };
    }

    public function dashboardKey(): string
    {
        return $this === self::MunicipalAdministrator
            ? 'administrator'
            : $this->value;
    }

    public function isMunicipalBackoffice(): bool
    {
        return match ($this) {
            self::MunicipalAdministrator,
            self::MunicipalTechnician,
            self::Jury,
            self::LegalManager,
            self::FinancialManager,
            self::HousingManager,
            self::MaintenanceManager,
            self::InspectionManager,
            self::SupportAgent,
            self::Auditor => true,
            self::PlatformAdministrator,
            self::Candidate,
            self::Unclassified => false,
        };
    }
}
