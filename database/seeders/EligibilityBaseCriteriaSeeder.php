<?php

namespace Database\Seeders;

use App\Enums\EligibilityCriterionCategory;
use App\Enums\EligibilityOperator;
use App\Enums\EligibilityRuleSetStatus;
use App\Models\EligibilityRuleSet;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;

class EligibilityBaseCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::query()->where('slug', 'arrendamento-acessivel-municipal')->first();

        if (! $program) {
            return;
        }

        $administrator = User::query()->where('email', 'admin@example.com')->first();
        $ruleSet = EligibilityRuleSet::query()->updateOrCreate(
            [
                'program_id' => $program->id,
                'contest_id' => null,
                'name' => 'Condições base — Arrendamento Acessível',
            ],
            [
                'description' => 'Regras institucionais fictícias para demonstração. Devem ser validadas juridicamente antes de produção.',
                'status' => EligibilityRuleSetStatus::Active->value,
                'is_default' => true,
                'starts_at' => now()->subDay(),
                'ends_at' => null,
                'created_by' => $administrator?->id,
                'updated_by' => $administrator?->id,
            ],
        );

        foreach ($this->criteria(false) as $criterion) {
            $ruleSet->criteria()->updateOrCreate(['code' => $criterion['code']], $criterion);
        }
    }

    /**
     * @return list<array<string, bool|int|string|null>>
     */
    public function criteria(bool $includeContest): array
    {
        $criteria = [
            $this->criterion('registration_is_registered', 'Registo de Adesão finalizado', EligibilityCriterionCategory::Identity, 'adhesion_registration', EligibilityOperator::IsTrue, 10),
            $this->criterion('candidate_is_adult', 'Candidato maior de idade', EligibilityCriterionCategory::Identity, 'adhesion_registration', EligibilityOperator::IsTrue, 20),
            $this->criterion('has_household', 'Agregado familiar preenchido', EligibilityCriterionCategory::Household, 'household', EligibilityOperator::IsTrue, 30),
            $this->criterion('has_applicant_member', 'Membro requerente identificado', EligibilityCriterionCategory::Household, 'household_member', EligibilityOperator::IsTrue, 40),
            $this->criterion('has_income_information', 'Informação de rendimentos completa', EligibilityCriterionCategory::Income, 'income_records', EligibilityOperator::IsTrue, 50),
            [
                ...$this->criterion('income_above_minimum', 'Rendimento anual acima do mínimo', EligibilityCriterionCategory::Income, 'calculated_value', EligibilityOperator::GreaterThanOrEqual, 60),
                'minimum_value' => 1,
                'unit' => 'EUR/ano',
                'is_active' => false,
            ],
            [
                ...$this->criterion('income_below_maximum', 'Rendimento anual abaixo do máximo', EligibilityCriterionCategory::Income, 'calculated_value', EligibilityOperator::LessThanOrEqual, 70),
                'maximum_value' => 60000,
                'unit' => 'EUR/ano',
                'is_active' => false,
            ],
            $this->criterion('has_current_housing_situation', 'Situação habitacional preenchida', EligibilityCriterionCategory::Housing, 'current_housing_situation', EligibilityOperator::IsTrue, 80),
            [
                ...$this->criterion('resides_in_municipality', 'Residência no município', EligibilityCriterionCategory::Residence, 'current_housing_situation', EligibilityOperator::IsTrue, 90),
                'is_active' => false,
            ],
            [
                ...$this->criterion('works_in_municipality', 'Atividade profissional no município', EligibilityCriterionCategory::Residence, 'current_housing_situation', EligibilityOperator::IsTrue, 100),
                'is_active' => false,
            ],
            $this->criterion('has_required_documents_submitted', 'Documentos obrigatórios submetidos', EligibilityCriterionCategory::Documents, 'documents', EligibilityOperator::AllRequiredDocumentsSubmitted, 110),
            [
                ...$this->criterion('has_required_documents_validated', 'Documentos obrigatórios validados', EligibilityCriterionCategory::Documents, 'documents', EligibilityOperator::AllRequiredDocumentsValidated, 120),
                'is_active' => false,
            ],
            [
                ...$this->criterion('typology_is_adequate', 'Adequação tipológica', EligibilityCriterionCategory::Typology, 'calculated_value', EligibilityOperator::Custom, 140),
                'is_active' => false,
            ],
            [
                ...$this->criterion('no_declared_property_impediment', 'Ausência de impedimento habitacional declarado', EligibilityCriterionCategory::LegalImpediments, 'manual', EligibilityOperator::Custom, 150),
                'is_active' => false,
                'requires_manual_review' => true,
            ],
            [
                ...$this->criterion('no_incompatible_housing_support', 'Ausência de apoio incompatível', EligibilityCriterionCategory::LegalImpediments, 'manual', EligibilityOperator::Custom, 160),
                'is_active' => false,
                'requires_manual_review' => true,
            ],
            [
                ...$this->criterion('requires_manual_review_for_special_conditions', 'Análise de condições especiais', EligibilityCriterionCategory::SpecialCondition, 'manual', EligibilityOperator::Custom, 170),
                'is_mandatory' => false,
                'requires_manual_review' => true,
            ],
        ];

        if ($includeContest) {
            $criteria[] = $this->criterion('contest_is_open', 'Prazo de candidatura aberto', EligibilityCriterionCategory::Application, 'contest', EligibilityOperator::IsTrue, 25);
            $criteria[] = $this->criterion('no_duplicate_active_application', 'Sem candidatura ativa duplicada', EligibilityCriterionCategory::Application, 'application', EligibilityOperator::IsTrue, 130);
        }

        return $criteria;
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function criterion(
        string $code,
        string $name,
        EligibilityCriterionCategory $category,
        string $target,
        EligibilityOperator $operator,
        int $sortOrder,
    ): array {
        return [
            'code' => $code,
            'name' => $name,
            'description' => 'Critério configurável do motor de elegibilidade.',
            'category' => $category->value,
            'target' => $target,
            'operator' => $operator->value,
            'expected_value' => null,
            'minimum_value' => null,
            'maximum_value' => null,
            'unit' => null,
            'is_mandatory' => true,
            'requires_manual_review' => false,
            'failure_message' => 'Esta condição mínima não se encontra cumprida.',
            'success_message' => 'Condição cumprida com base nos dados declarados.',
            'review_message' => 'Este elemento requer análise pelos serviços municipais.',
            'sort_order' => $sortOrder,
            'is_active' => true,
        ];
    }
}
