<?php

namespace Database\Seeders;

use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentCategory;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['documento_identificacao', 'Documento de identificação', DocumentCategory::Identification, DocumentAppliesTo::HouseholdMember],
            ['nif', 'Comprovativo de NIF', DocumentCategory::Tax, DocumentAppliesTo::HouseholdMember],
            ['titulo_residencia', 'Título de residência', DocumentCategory::Identification, DocumentAppliesTo::HouseholdMember],
            ['comprovativo_domicilio_fiscal', 'Comprovativo de domicílio fiscal', DocumentCategory::Tax, DocumentAppliesTo::HouseholdMember],
            ['certidao_predial_negativa', 'Certidão predial negativa', DocumentCategory::Housing, DocumentAppliesTo::HouseholdMember],
            ['irs', 'Declaração de IRS', DocumentCategory::Income, DocumentAppliesTo::HouseholdMember],
            ['nota_liquidacao_irs', 'Nota de liquidação de IRS', DocumentCategory::Income, DocumentAppliesTo::HouseholdMember],
            ['recibos_vencimento', 'Recibos de vencimento', DocumentCategory::Income, DocumentAppliesTo::IncomeRecord],
            ['declaracao_seg_social', 'Declaração da Segurança Social', DocumentCategory::SocialSecurity, DocumentAppliesTo::IncomeRecord],
            ['comprovativo_pensao', 'Comprovativo de pensão', DocumentCategory::Income, DocumentAppliesTo::IncomeRecord],
            ['comprovativo_subsidio_desemprego', 'Comprovativo de subsídio de desemprego', DocumentCategory::Income, DocumentAppliesTo::IncomeRecord],
            ['atestado_incapacidade', 'Atestado médico de incapacidade multiuso', DocumentCategory::Health, DocumentAppliesTo::HouseholdMember],
            ['comprovativo_estudante', 'Comprovativo de estudante', DocumentCategory::Education, DocumentAppliesTo::HouseholdMember],
            ['contrato_arrendamento_atual', 'Contrato de arrendamento atual', DocumentCategory::Housing, DocumentAppliesTo::CurrentHousingSituation],
            ['recibo_renda', 'Recibo de renda', DocumentCategory::Housing, DocumentAppliesTo::CurrentHousingSituation],
            ['declaracao_honra', 'Declaração sob compromisso de honra', DocumentCategory::Declaration, DocumentAppliesTo::AdhesionRegistration],
        ])->each(function (array $definition, int $index) {
            [$code, $name, $category, $appliesTo] = $definition;

            DocumentType::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => 'Documento exigível para preparação de candidatura municipal.',
                    'category' => $category->value,
                    'applies_to' => $appliesTo->value,
                    'is_active' => true,
                    'is_required_by_default' => true,
                    'requires_expiry_date' => false,
                    'requires_issue_date' => false,
                    'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                    'max_file_size_mb' => 10,
                    'sort_order' => ($index + 1) * 10,
                ],
            );
        });
    }
}
