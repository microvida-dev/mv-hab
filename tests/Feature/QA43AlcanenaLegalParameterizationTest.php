<?php

namespace Tests\Feature;

use App\Enums\AllocationMethod;
use App\Enums\ContestDeadlineType;
use App\Models\AllocationRuleSet;
use App\Models\Contest;
use App\Models\DocumentType;
use App\Models\EligibilityRuleSet;
use App\Models\RentRuleSet;
use App\Models\RequiredDocument;
use App\Models\ScoringRuleSet;
use Database\Seeders\DemoAlcanenaAffordableRentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QA43AlcanenaLegalParameterizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_alcanena_contest_has_core_legal_parameters(): void
    {
        $this->seed(DemoAlcanenaAffordableRentSeeder::class);

        $contest = Contest::query()
            ->where('code', DemoAlcanenaAffordableRentSeeder::CONTEST_CODE)
            ->firstOrFail();

        $this->assertSame('Município de Alcanena', $contest->program->municipality->name);
        $this->assertStringContainsString('Regulamento Municipal de Arrendamento Acessível de Alcanena', $contest->program->legal_basis);
        $this->assertSame(5, $contest->deadlines()->count());
        $this->assertTrue($contest->deadlines()->where('type', ContestDeadlineType::Complaints->value)->exists());
        $this->assertTrue($contest->deadlines()->where('type', ContestDeadlineType::Hearing->value)->exists());
    }

    public function test_alcanena_eligibility_scoring_allocation_and_rent_are_parameterized(): void
    {
        $this->seed(DemoAlcanenaAffordableRentSeeder::class);

        $contest = Contest::query()->where('code', DemoAlcanenaAffordableRentSeeder::CONTEST_CODE)->firstOrFail();

        $eligibility = EligibilityRuleSet::query()->where('contest_id', $contest->id)->firstOrFail();
        $this->assertSame(22, $eligibility->criteria()->count());
        $this->assertTrue($eligibility->criteria()->where('code', 'candidate_is_adult')->exists());
        $this->assertTrue($eligibility->criteria()->where('code', 'rent_effort_within_35_percent')->exists());
        $this->assertSame(7, $eligibility->criteria()->where('requires_manual_review', true)->count());

        $scoring = ScoringRuleSet::query()->where('contest_id', $contest->id)->firstOrFail();
        $this->assertSame(4, $scoring->criteria()->count());
        $this->assertSame(4, $scoring->tieBreakerRules()->count());

        $allocation = AllocationRuleSet::query()->where('contest_id', $contest->id)->firstOrFail();
        $this->assertSame(AllocationMethod::RankingThenLottery, $allocation->allocation_method);
        $this->assertTrue($allocation->allow_lottery);
        $this->assertFalse($allocation->allow_manual_override);

        $rent = RentRuleSet::query()->where('contest_id', $contest->id)->firstOrFail();
        $this->assertSame('35.00', $rent->effort_rate_percentage);
        $this->assertSame('1.00', $rent->deposit_months);
    }

    public function test_alcanena_document_checklist_covers_article_12_and_conditional_documents(): void
    {
        $this->seed(DemoAlcanenaAffordableRentSeeder::class);

        $contest = Contest::query()->where('code', DemoAlcanenaAffordableRentSeeder::CONTEST_CODE)->firstOrFail();
        $codes = DocumentType::query()
            ->where('code', 'like', 'alcanena_%')
            ->pluck('code')
            ->all();

        foreach ([
            'alcanena_identificacao_residencia',
            'alcanena_nif',
            'alcanena_seguranca_social',
            'alcanena_domicilio_fiscal',
            'alcanena_nota_liquidacao_irs',
            'alcanena_rendimentos_dispensa_irs',
            'alcanena_certidao_predial',
            'alcanena_situacao_regular_at',
            'alcanena_situacao_regular_iss',
            'alcanena_atestado_incapacidade',
            'alcanena_declaracao_gravidez',
        ] as $code) {
            $this->assertContains($code, $codes);
        }

        $this->assertSame(11, RequiredDocument::query()->where('contest_id', $contest->id)->count());
        $this->assertDatabaseHas('required_documents', [
            'contest_id' => $contest->id,
            'condition_key' => 'household_member.is_pregnant',
            'condition_operator' => 'is_true',
            'is_required' => true,
        ]);
    }
}
