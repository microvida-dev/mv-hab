<?php

namespace Database\Seeders;

use App\Enums\ScoringCalculationType;
use App\Enums\ScoringOperator;
use App\Enums\ScoringRuleSetStatus;
use App\Enums\TieBreakerDirection;
use App\Models\Program;
use App\Models\ScoringRuleSet;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScoringBaseCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::query()->where('slug', 'arrendamento-acessivel-municipal')->first();

        if (! $program) {
            return;
        }

        $administrator = User::query()->where('email', 'admin@example.com')->first();
        $ruleSet = ScoringRuleSet::query()->updateOrCreate(
            [
                'program_id' => $program->id,
                'contest_id' => null,
                'name' => 'Classificação base - Arrendamento Acessível',
            ],
            [
                'description' => 'Matriz fictícia para demonstração municipal. Requer validação jurídica antes de produção.',
                'status' => ScoringRuleSetStatus::Active->value,
                'is_default' => true,
                'starts_at' => now()->subDay(),
                'ends_at' => null,
                'created_by' => $administrator?->id,
                'updated_by' => $administrator?->id,
            ],
        );

        foreach ($this->criteria() as $criterion) {
            $ruleSet->criteria()->updateOrCreate(['code' => $criterion['code']], $criterion);
        }

        $this->tieBreakers($ruleSet);
    }

    /**
     * @return list<array<string, bool|float|int|string|array<string, list<string>>|null>>
     */
    public function criteria(): array
    {
        return [
            $this->criterion('monthly_income_per_capita', 'Rendimento mensal per capita', 'income', 'calculated_value', ScoringCalculationType::Threshold, ScoringOperator::LessThanOrEqual, 20, maximum: 600),
            $this->criterion('number_of_dependents', 'Número de dependentes', 'dependency', 'household', ScoringCalculationType::Threshold, ScoringOperator::GreaterThanOrEqual, 10, minimum: 1),
            $this->criterion('resides_in_municipality', 'Residência no município', 'residence', 'current_housing_situation', ScoringCalculationType::Boolean, ScoringOperator::IsTrue, 8),
            $this->criterion('works_in_municipality', 'Trabalho no município', 'employment', 'current_housing_situation', ScoringCalculationType::Boolean, ScoringOperator::IsTrue, 5),
            $this->criterion('number_of_disabled_members', 'Membro com deficiência/incapacidade', 'disability', 'household', ScoringCalculationType::Threshold, ScoringOperator::GreaterThanOrEqual, 10, minimum: 1),
            $this->criterion('risk_of_eviction', 'Risco de despejo declarado', 'vulnerability', 'current_housing_situation', ScoringCalculationType::Boolean, ScoringOperator::IsTrue, 12),
            $this->criterion('homelessness', 'Situação de sem-abrigo', 'vulnerability', 'current_housing_situation', ScoringCalculationType::Boolean, ScoringOperator::IsTrue, 15),
            [
                ...$this->criterion('housing_status', 'Situação habitacional precária', 'housing_situation', 'current_housing_situation', ScoringCalculationType::Threshold, ScoringOperator::In, 10),
                'expected_value' => ['values' => ['homeless', 'temporary', 'institutional']],
            ],
            [
                ...$this->criterion('manual_assessment', 'Apreciação técnica manual', 'manual_assessment', 'manual', ScoringCalculationType::Manual, ScoringOperator::Custom, 0),
                'max_points' => 10,
                'requires_manual_review' => true,
                'review_message' => 'Pontuação dependente de apreciação técnica autorizada.',
            ],
        ];
    }

    /**
     * @return array<string, bool|float|int|string|array<string, list<string>>|null>
     */
    private function criterion(
        string $code,
        string $name,
        string $category,
        string $target,
        ScoringCalculationType $type,
        ScoringOperator $operator,
        int $points,
        ?float $minimum = null,
        ?float $maximum = null,
    ): array {
        return [
            'code' => $code,
            'name' => $name,
            'description' => 'Critério fictício configurável da matriz de classificação.',
            'category' => $category,
            'target' => $target,
            'calculation_type' => $type->value,
            'operator' => $operator->value,
            'expected_value' => null,
            'minimum_value' => $minimum,
            'maximum_value' => $maximum,
            'points' => $points,
            'max_points' => $points,
            'weight' => 1,
            'requires_manual_review' => false,
            'is_exclusionary' => false,
            'is_active' => true,
            'sort_order' => $points,
            'success_message' => 'Critério pontuado com base nos dados declarados.',
            'failure_message' => 'Critério avaliado sem atribuição de pontos.',
            'review_message' => 'Critério requer apreciação municipal.',
        ];
    }

    public function tieBreakers(ScoringRuleSet $ruleSet): void
    {
        $ruleSet->tieBreakerRules()->updateOrCreate(
            ['code' => 'monthly_income_per_capita'],
            [
                'name' => 'Menor rendimento per capita',
                'description' => 'Desempate fictício por rendimento per capita mais baixo.',
                'target' => 'monthly_income_per_capita',
                'direction' => TieBreakerDirection::Asc->value,
                'priority_order' => 10,
                'is_active' => true,
            ],
        );

        $ruleSet->tieBreakerRules()->updateOrCreate(
            ['code' => 'submitted_at'],
            [
                'name' => 'Submissão mais antiga',
                'description' => 'Desempate fictício por data de submissão mais antiga.',
                'target' => 'submitted_at',
                'direction' => TieBreakerDirection::Asc->value,
                'priority_order' => 20,
                'is_active' => true,
            ],
        );
    }
}
