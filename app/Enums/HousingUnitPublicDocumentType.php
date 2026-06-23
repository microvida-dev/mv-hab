<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum HousingUnitPublicDocumentType: string
{
    use HasOptions;

    case Brochure = 'brochure';
    case TechnicalSheet = 'technical_sheet';
    case FloorPlan = 'floor_plan';
    case EnergyCertificatePublic = 'energy_certificate_public';
    case ContestNotice = 'contest_notice';
    case ContractTemplatePublic = 'contract_template_public';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Brochure => 'Brochura',
            self::TechnicalSheet => 'Ficha técnica',
            self::FloorPlan => 'Planta',
            self::EnergyCertificatePublic => 'Certificado energético público',
            self::ContestNotice => 'Aviso de concurso',
            self::ContractTemplatePublic => 'Minuta pública',
            self::Other => 'Outro documento',
        };
    }
}
