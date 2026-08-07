<?php

namespace Database\Seeders\Production;

use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentCategory;
use App\Enums\RequiredDocumentConditionOperator;
use App\Models\Contest;
use App\Models\DocumentType;
use App\Models\Program;
use App\Models\RequiredDocument;
use DomainException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class AlcanenaRequiredDocumentsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            [$program, $contest] = $this->programAndContest();

            foreach ($this->documentTypes() as $definition) {
                $this->createDocumentTypeIfMissing($definition);
            }

            foreach ($this->requirements() as $definition) {
                $this->createRequirementIfMissing($program, $contest, $definition);
            }
        }, 3);

        $this->command->info('Checklist documental de produção de Alcanena criada sem substituir configuração existente.');
    }

    /** @return array{0: Program, 1: Contest} */
    private function programAndContest(): array
    {
        $program = Program::withTrashed()
            ->where('slug', AlcanenaProductionSeeder::PROGRAM_SLUG)
            ->first();

        if (! $program instanceof Program) {
            throw new DomainException('O Programa de produção de Alcanena não existe.');
        }

        if ($program->trashed()) {
            throw new DomainException('O Programa de produção de Alcanena encontra-se eliminado.');
        }

        $contest = Contest::withTrashed()
            ->where('code', AlcanenaProductionSeeder::CONTEST_CODE)
            ->first();

        if (! $contest instanceof Contest) {
            throw new DomainException('O Concurso de produção de Alcanena não existe.');
        }

        if ($contest->trashed()) {
            throw new DomainException('O Concurso de produção de Alcanena encontra-se eliminado.');
        }

        if ((int) $contest->program_id !== (int) $program->id) {
            throw new DomainException('O Concurso de produção de Alcanena não pertence ao Programa esperado.');
        }

        return [$program, $contest];
    }

    /**
     * @param  array{
     *     code: string,
     *     name: string,
     *     description: string,
     *     category: string,
     *     applies_to: string,
     *     is_active: bool,
     *     is_required_by_default: bool,
     *     requires_expiry_date: bool,
     *     requires_issue_date: bool,
     *     allowed_mime_types: list<string>,
     *     max_file_size_mb: int,
     *     sort_order: int
     * }  $definition
     */
    private function createDocumentTypeIfMissing(array $definition): void
    {
        $documentType = DocumentType::withTrashed()
            ->where('code', $definition['code'])
            ->first();

        if ($documentType instanceof DocumentType) {
            if ($documentType->trashed()) {
                throw new DomainException(
                    "O tipo documental {$definition['code']} existe, mas encontra-se eliminado.",
                );
            }

            if (
                (string) $documentType->getRawOriginal('category') !== $definition['category']
                || (string) $documentType->getRawOriginal('applies_to') !== $definition['applies_to']
            ) {
                throw new DomainException(
                    "O tipo documental {$definition['code']} existe com configuração estrutural incompatível.",
                );
            }

            return;
        }

        DocumentType::query()->create($definition);
    }

    /**
     * @param  array{
     *     document_code: string,
     *     required_for: string,
     *     condition_key: string,
     *     condition_operator: string,
     *     condition_value: string|null,
     *     is_required: bool,
     *     is_active: bool,
     *     required_submissions: int,
     *     reference_period_unit: null,
     *     requires_distinct_reference_periods: bool,
     *     reference_period_recency: null,
     *     instructions: string,
     *     sort_order: int
     * }  $definition
     */
    private function createRequirementIfMissing(
        Program $program,
        Contest $contest,
        array $definition,
    ): void {
        $documentType = DocumentType::query()
            ->where('code', $definition['document_code'])
            ->first();

        if (! $documentType instanceof DocumentType) {
            throw new DomainException(
                "O tipo documental {$definition['document_code']} não existe.",
            );
        }

        $identity = [
            'document_type_id' => $documentType->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'required_for' => $definition['required_for'],
            'condition_key' => $definition['condition_key'],
            'condition_operator' => $definition['condition_operator'],
            'condition_value' => $definition['condition_value'],
        ];

        $requiredDocument = RequiredDocument::withTrashed()
            ->where($identity)
            ->first();

        if ($requiredDocument instanceof RequiredDocument) {
            if ($requiredDocument->trashed()) {
                throw new DomainException(
                    "O requisito documental {$definition['document_code']} existe, mas encontra-se eliminado.",
                );
            }

            return;
        }

        $conflict = RequiredDocument::withTrashed()
            ->where('document_type_id', $documentType->id)
            ->where('program_id', $program->id)
            ->where('contest_id', $contest->id)
            ->first();

        if ($conflict instanceof RequiredDocument) {
            throw new DomainException(
                "O requisito documental {$definition['document_code']} já existe com outra condição.",
            );
        }

        RequiredDocument::query()->create([
            ...$identity,
            'is_required' => $definition['is_required'],
            'is_active' => $definition['is_active'],
            'required_submissions' => $definition['required_submissions'],
            'reference_period_unit' => $definition['reference_period_unit'],
            'requires_distinct_reference_periods' => $definition['requires_distinct_reference_periods'],
            'reference_period_recency' => $definition['reference_period_recency'],
            'instructions' => $definition['instructions'],
            'sort_order' => $definition['sort_order'],
        ]);
    }

    /**
     * @return list<array{
     *     code: string,
     *     name: string,
     *     description: string,
     *     category: string,
     *     applies_to: string,
     *     is_active: bool,
     *     is_required_by_default: bool,
     *     requires_expiry_date: bool,
     *     requires_issue_date: bool,
     *     allowed_mime_types: list<string>,
     *     max_file_size_mb: int,
     *     sort_order: int
     * }>
     */
    private function documentTypes(): array
    {
        $definitions = [
            ['alcanena_identificacao_residencia', 'Identificação civil ou autorização de residência', DocumentCategory::Identification, DocumentAppliesTo::HouseholdMember],
            ['alcanena_nif', 'Cartão ou comprovativo de NIF', DocumentCategory::Tax, DocumentAppliesTo::HouseholdMember],
            ['alcanena_seguranca_social', 'Cartão ou comprovativo de Segurança Social', DocumentCategory::SocialSecurity, DocumentAppliesTo::HouseholdMember],
            ['alcanena_domicilio_fiscal', 'Certidão de domicílio fiscal', DocumentCategory::Tax, DocumentAppliesTo::HouseholdMember],
            ['alcanena_nota_liquidacao_irs', 'Nota de liquidação de IRS do ano fiscal anterior', DocumentCategory::Income, DocumentAppliesTo::Household],
            ['alcanena_rendimentos_dispensa_irs', 'Comprovativos de rendimentos por dispensa de IRS', DocumentCategory::Income, DocumentAppliesTo::HouseholdMember],
            ['alcanena_certidao_predial', 'Certidão da AT relativa à propriedade habitacional', DocumentCategory::Housing, DocumentAppliesTo::HouseholdMember],
            ['alcanena_situacao_regular_at', 'Certidão de situação regularizada na AT', DocumentCategory::Tax, DocumentAppliesTo::AdhesionRegistration],
            ['alcanena_situacao_regular_iss', 'Certidão de situação regularizada no ISS', DocumentCategory::SocialSecurity, DocumentAppliesTo::AdhesionRegistration],
            ['alcanena_atestado_incapacidade', 'Atestado médico de incapacidade multiúso', DocumentCategory::Health, DocumentAppliesTo::HouseholdMember],
            ['alcanena_declaracao_gravidez', 'Declaração médica de gravidez', DocumentCategory::Health, DocumentAppliesTo::HouseholdMember],
        ];

        $documentTypes = [];

        foreach ($definitions as $index => [$code, $name, $category, $appliesTo]) {
            $documentTypes[] = [
                'code' => $code,
                'name' => $name,
                'description' => 'Documento da checklist do artigo 12.º do Regulamento Municipal de Arrendamento Acessível de Alcanena.',
                'category' => $category->value,
                'applies_to' => $appliesTo->value,
                'is_active' => true,
                'is_required_by_default' => false,
                'requires_expiry_date' => false,
                'requires_issue_date' => false,
                'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
                'max_file_size_mb' => 10,
                'sort_order' => 1000 + (($index + 1) * 10),
            ];
        }

        return $documentTypes;
    }

    /**
     * @return list<array{
     *     document_code: string,
     *     required_for: string,
     *     condition_key: string,
     *     condition_operator: string,
     *     condition_value: string|null,
     *     is_required: bool,
     *     is_active: bool,
     *     required_submissions: int,
     *     reference_period_unit: null,
     *     requires_distinct_reference_periods: bool,
     *     reference_period_recency: null,
     *     instructions: string,
     *     sort_order: int
     * }>
     */
    private function requirements(): array
    {
        $definitions = [
            ['alcanena_identificacao_residencia', DocumentAppliesTo::HouseholdMember, 'always', RequiredDocumentConditionOperator::Always, null, 'Obrigatório para todos os elementos. Para cidadãos estrangeiros, anexar autorização de residência válida.'],
            ['alcanena_nif', DocumentAppliesTo::HouseholdMember, 'always', RequiredDocumentConditionOperator::Always, null, 'Obrigatório para todos os elementos, quando aplicável.'],
            ['alcanena_seguranca_social', DocumentAppliesTo::HouseholdMember, 'always', RequiredDocumentConditionOperator::Always, null, 'Obrigatório para todos os elementos, quando aplicável.'],
            ['alcanena_domicilio_fiscal', DocumentAppliesTo::HouseholdMember, 'always', RequiredDocumentConditionOperator::Always, null, 'Certidão individual de domicílio fiscal.'],
            ['alcanena_nota_liquidacao_irs', DocumentAppliesTo::Household, 'always', RequiredDocumentConditionOperator::Always, null, 'Nota de liquidação relativa à totalidade do agregado e ao ano fiscal anterior; quando ainda indisponível, a validação municipal deve aceitar o comprovativo alternativo previsto no artigo 12.º.'],
            ['alcanena_rendimentos_dispensa_irs', DocumentAppliesTo::HouseholdMember, 'household_member.is_exempt_from_irs', RequiredDocumentConditionOperator::IsTrue, null, 'Aplicável a membros dispensados de entregar IRS que aufiram rendimentos.'],
            ['alcanena_certidao_predial', DocumentAppliesTo::HouseholdMember, 'always', RequiredDocumentConditionOperator::Always, null, 'Certidão da Autoridade Tributária que permita validar o impedimento relativo a imóvel destinado a habitação.'],
            ['alcanena_situacao_regular_at', DocumentAppliesTo::AdhesionRegistration, 'always', RequiredDocumentConditionOperator::Always, null, 'Certidão comprovativa de situação tributária regularizada junto da Autoridade Tributária.'],
            ['alcanena_situacao_regular_iss', DocumentAppliesTo::AdhesionRegistration, 'always', RequiredDocumentConditionOperator::Always, null, 'Certidão comprovativa de situação contributiva regularizada junto do ISS, I. P.'],
            ['alcanena_atestado_incapacidade', DocumentAppliesTo::HouseholdMember, 'household_member.is_disabled', RequiredDocumentConditionOperator::IsTrue, null, 'Aplicável a elementos com deficiência ou multideficiência declarada.'],
            ['alcanena_declaracao_gravidez', DocumentAppliesTo::HouseholdMember, 'household_member.is_pregnant', RequiredDocumentConditionOperator::IsTrue, null, 'Aplicável ao elemento que declare gravidez.'],
        ];

        $requirements = [];

        foreach ($definitions as $index => [$code, $requiredFor, $conditionKey, $operator, $conditionValue, $instructions]) {
            $requirements[] = [
                'document_code' => $code,
                'required_for' => $requiredFor->value,
                'condition_key' => $conditionKey,
                'condition_operator' => $operator->value,
                'condition_value' => $conditionValue,
                'is_required' => true,
                'is_active' => true,
                'required_submissions' => 1,
                'reference_period_unit' => null,
                'requires_distinct_reference_periods' => false,
                'reference_period_recency' => null,
                'instructions' => $instructions,
                'sort_order' => ($index + 1) * 10,
            ];
        }

        return $requirements;
    }
}
