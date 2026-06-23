<?php

namespace Database\Seeders;

use App\Enums\DocumentAppliesTo;
use App\Enums\HousingStatus;
use App\Enums\IncomeSourceType;
use App\Enums\RequiredDocumentConditionOperator;
use App\Models\DocumentType;
use App\Models\RequiredDocument;
use Illuminate\Database\Seeder;

class RequiredDocumentSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['documento_identificacao', DocumentAppliesTo::HouseholdMember, 'always', RequiredDocumentConditionOperator::Always, null, 'Documento obrigatório para cada membro do agregado.'],
            ['nif', DocumentAppliesTo::HouseholdMember, 'household_member.is_adult', RequiredDocumentConditionOperator::IsTrue, null, 'Obrigatório para membros maiores de idade.'],
            ['comprovativo_domicilio_fiscal', DocumentAppliesTo::HouseholdMember, 'household_member.is_adult', RequiredDocumentConditionOperator::IsTrue, null, 'Comprovativo de domicílio fiscal para membros maiores de idade.'],
            ['certidao_predial_negativa', DocumentAppliesTo::HouseholdMember, 'household_member.is_adult', RequiredDocumentConditionOperator::IsTrue, null, 'Certidão predial negativa para membros maiores de idade.'],
            ['irs', DocumentAppliesTo::HouseholdMember, 'household_member.is_adult', RequiredDocumentConditionOperator::IsTrue, null, 'Declaração de IRS quando aplicável.'],
            ['nota_liquidacao_irs', DocumentAppliesTo::HouseholdMember, 'household_member.is_adult', RequiredDocumentConditionOperator::IsTrue, null, 'Nota de liquidação de IRS quando aplicável.'],
            ['recibos_vencimento', DocumentAppliesTo::IncomeRecord, 'income_record.income_source', RequiredDocumentConditionOperator::Equals, IncomeSourceType::Employment->value, 'Comprovativo para rendimentos de trabalho dependente.'],
            ['declaracao_seg_social', DocumentAppliesTo::IncomeRecord, 'income_record.income_source', RequiredDocumentConditionOperator::Equals, IncomeSourceType::SocialBenefit->value, 'Declaração da Segurança Social para prestações sociais.'],
            ['declaracao_seg_social', DocumentAppliesTo::IncomeRecord, 'income_record.income_source', RequiredDocumentConditionOperator::Equals, IncomeSourceType::UnemploymentBenefit->value, 'Declaração da Segurança Social para subsídio de desemprego.'],
            ['comprovativo_pensao', DocumentAppliesTo::IncomeRecord, 'income_record.income_source', RequiredDocumentConditionOperator::Equals, IncomeSourceType::Pension->value, 'Comprovativo de pensão.'],
            ['comprovativo_subsidio_desemprego', DocumentAppliesTo::IncomeRecord, 'income_record.income_source', RequiredDocumentConditionOperator::Equals, IncomeSourceType::UnemploymentBenefit->value, 'Comprovativo de subsídio de desemprego.'],
            ['atestado_incapacidade', DocumentAppliesTo::HouseholdMember, 'household_member.is_disabled', RequiredDocumentConditionOperator::IsTrue, null, 'Obrigatório quando existe incapacidade declarada.'],
            ['comprovativo_estudante', DocumentAppliesTo::HouseholdMember, 'household_member.is_student', RequiredDocumentConditionOperator::IsTrue, null, 'Obrigatório quando o membro declara ser estudante.'],
            ['contrato_arrendamento_atual', DocumentAppliesTo::CurrentHousingSituation, 'current_housing_situation.housing_status', RequiredDocumentConditionOperator::Equals, HousingStatus::Rented->value, 'Obrigatório quando a habitação atual é arrendada.'],
            ['recibo_renda', DocumentAppliesTo::CurrentHousingSituation, 'current_housing_situation.current_monthly_rent', RequiredDocumentConditionOperator::Exists, null, 'Obrigatório quando existe renda mensal declarada.'],
            ['declaracao_honra', DocumentAppliesTo::AdhesionRegistration, 'always', RequiredDocumentConditionOperator::Always, null, 'Declaração sob compromisso de honra para preparação do processo.'],
        ])->each(function (array $definition, int $index) {
            [$documentCode, $requiredFor, $conditionKey, $operator, $conditionValue, $instructions] = $definition;
            $documentType = DocumentType::query()->where('code', $documentCode)->firstOrFail();

            RequiredDocument::query()->updateOrCreate(
                [
                    'document_type_id' => $documentType->id,
                    'required_for' => $requiredFor->value,
                    'condition_key' => $conditionKey,
                    'condition_operator' => $operator->value,
                    'condition_value' => $conditionValue,
                    'program_id' => null,
                    'contest_id' => null,
                ],
                [
                    'is_required' => true,
                    'is_active' => true,
                    'instructions' => $instructions,
                    'sort_order' => ($index + 1) * 10,
                ],
            );
        });
    }
}
