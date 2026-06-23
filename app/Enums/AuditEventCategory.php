<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AuditEventCategory: string
{
    use HasOptions;

    case Authentication = 'authentication';
    case Authorization = 'authorization';
    case CandidateData = 'candidate_data';
    case Application = 'application';
    case Documents = 'documents';
    case Workflow = 'workflow';
    case Scoring = 'scoring';
    case Allocation = 'allocation';
    case Contracts = 'contracts';
    case Finance = 'finance';
    case Maintenance = 'maintenance';
    case Communications = 'communications';
    case Reports = 'reports';
    case Rgpd = 'rgpd';
    case Security = 'security';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Authentication => 'Autenticação',
            self::Authorization => 'Autorização',
            self::CandidateData => 'Dados do titular',
            self::Application => 'Candidaturas',
            self::Documents => 'Documentos',
            self::Workflow => 'Workflow',
            self::Scoring => 'Classificação',
            self::Allocation => 'Atribuição',
            self::Contracts => 'Contratos',
            self::Finance => 'Financeiro',
            self::Maintenance => 'Manutenção',
            self::Communications => 'Comunicações',
            self::Reports => 'Relatórios',
            self::Rgpd => 'RGPD',
            self::Security => 'Segurança',
            self::System => 'Sistema',
        };
    }
}
