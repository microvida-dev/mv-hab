<?php

namespace App\Enums;

enum BackofficeRouteBoundedContext: string
{
    case AdministrationSecurity = 'administration_security';
    case UsersTeams = 'users_teams';
    case Applications = 'applications';
    case Documents = 'documents';
    case AdministrativeProcesses = 'administrative_processes';
    case Eligibility = 'eligibility';
    case Scoring = 'scoring';
    case Decisions = 'decisions';
    case Hearings = 'hearings';
    case Complaints = 'complaints';
    case Lists = 'lists';
    case Allocations = 'allocations';
    case Contracts = 'contracts';
    case Finance = 'finance';
    case Payments = 'payments';
    case Maintenance = 'maintenance';
    case Inspections = 'inspections';
    case Visits = 'visits';
    case Agenda = 'agenda';
    case Reports = 'reports';
    case Communications = 'communications';
    case Notifications = 'notifications';
    case Rgpd = 'rgpd';
    case Configuration = 'configuration';
    case Residual = 'residual';

    public function label(): string
    {
        return match ($this) {
            self::AdministrationSecurity => 'Administração e segurança',
            self::UsersTeams => 'Utilizadores e equipas',
            self::Applications => 'Candidaturas',
            self::Documents => 'Documentos',
            self::AdministrativeProcesses => 'Processos administrativos',
            self::Eligibility => 'Elegibilidade',
            self::Scoring => 'Classificação',
            self::Decisions => 'Decisões',
            self::Hearings => 'Audiência',
            self::Complaints => 'Reclamações',
            self::Lists => 'Listas',
            self::Allocations => 'Atribuições',
            self::Contracts => 'Contratos',
            self::Finance => 'Finanças',
            self::Payments => 'Pagamentos',
            self::Maintenance => 'Manutenção',
            self::Inspections => 'Vistorias',
            self::Visits => 'Visitas',
            self::Agenda => 'Agenda',
            self::Reports => 'Relatórios',
            self::Communications => 'Comunicações',
            self::Notifications => 'Notificações',
            self::Rgpd => 'RGPD',
            self::Configuration => 'Configurações',
            self::Residual => 'Residual/desconhecido',
        };
    }

    public function targetSprint(): string
    {
        return match ($this) {
            self::AdministrationSecurity, self::UsersTeams, self::Rgpd => '47A',
            self::Applications, self::Documents, self::AdministrativeProcesses => '47B',
            self::Eligibility, self::Scoring, self::Decisions => '47C',
            self::Hearings, self::Complaints, self::Lists, self::Allocations => '47D',
            self::Contracts => '47E',
            self::Finance, self::Payments => '47F',
            self::Maintenance, self::Inspections, self::Visits, self::Agenda => '47G',
            self::Reports, self::Communications, self::Notifications,
            self::Configuration, self::Residual => '47H',
        };
    }
}
