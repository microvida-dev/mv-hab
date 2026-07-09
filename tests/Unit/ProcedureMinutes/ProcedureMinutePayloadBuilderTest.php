<?php

namespace Tests\Unit\ProcedureMinutes;

use App\Models\AdministrativeDecision;
use App\Models\Application;
use App\Models\ApplicationPreference;
use App\Models\ApplicationScore;
use App\Models\Complaint;
use App\Models\ComplaintDecision;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\ContestJuryMember;
use App\Models\ControlledWithdrawal;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use App\Models\Hearing;
use App\Models\HearingSubmission;
use App\Models\HousingUnit;
use App\Models\LotteryDraw;
use App\Models\LotteryParticipant;
use App\Models\LotteryResult;
use App\Models\ProcessConfirmation;
use App\Models\Program;
use App\Models\ProvisionalList;
use App\Models\ProvisionalListEntry;
use App\Models\User;
use App\Services\ProcedureMinutes\ProcedureMinutePayloadBuilder;
use App\Services\ProcedureTemplates\TemplateVariableResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcedureMinutePayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_payload_builder_generates_complete_contest_payload(): void
    {
        [$contest, $application] = $this->procedureDataSet();

        $payload = app(ProcedureMinutePayloadBuilder::class)->build([
            'contest_id' => $contest->id,
            'application_id' => $application->id,
            'subject' => 'Ata de procedimento',
            'meeting_date' => '2026-07-08',
            'meeting_time' => '11:00',
            'meeting_location' => 'Alcanena',
            'legal_basis' => 'Regulamento municipal.',
        ], User::factory()->create(['name' => 'Administrador']));

        $this->assertSame('Município de Alcanena', data_get($payload, 'municipal.municipality_name'));
        $this->assertSame($contest->title, data_get($payload, 'contest.title'));
        $this->assertCount(1, data_get($payload, 'jury'));
        $this->assertCount(1, data_get($payload, 'housing_units'));
        $this->assertCount(1, data_get($payload, 'applications'));
        $this->assertCount(1, data_get($payload, 'provisional_lists'));
        $this->assertCount(1, data_get($payload, 'definitive_lists'));
        $this->assertCount(1, data_get($payload, 'hearings'));
        $this->assertCount(1, data_get($payload, 'complaints'));
        $this->assertCount(1, data_get($payload, 'lottery_draws'));
        $this->assertCount(1, data_get($payload, 'withdrawals'));
        $this->assertCount(1, data_get($payload, 'administrative_decisions'));
        $this->assertSame(1, data_get($payload, 'summary.applications_total'));
        $this->assertSame(1, data_get($payload, 'summary.housing_units_total'));
        $this->assertSame(1, data_get($payload, 'summary.provisional_lists_total'));
        $this->assertSame(1, data_get($payload, 'summary.definitive_lists_total'));
        $this->assertSame(1, data_get($payload, 'summary.lottery_draws_total'));
    }

    public function test_template_variable_resolver_resolves_main_summaries(): void
    {
        [$contest, $application] = $this->procedureDataSet();
        $payload = app(ProcedureMinutePayloadBuilder::class)->build([
            'contest_id' => $contest->id,
            'application_id' => $application->id,
            'subject' => 'Ata de procedimento',
            'legal_basis' => 'Regulamento municipal.',
            'deliberation_text' => 'Deliberação municipal.',
            'observations' => 'Observação interna.',
        ]);

        $variables = app(TemplateVariableResolver::class)->forProcedureMinutePayload($payload);

        $this->assertSame('Município de Alcanena', $variables['municipality_name']);
        $this->assertSame($contest->title, $variables['contest_title']);
        $this->assertSame($application->application_number, $variables['application_number']);
        $this->assertStringContainsString('Presidente', $variables['jury_members']);
        $this->assertStringContainsString('ALC-T2-001', $variables['housing_units_summary']);
        $this->assertStringContainsString((string) $application->application_number, $variables['applications_summary']);
        $this->assertStringContainsString('LP-', $variables['provisional_list_summary']);
        $this->assertStringContainsString('Sorteio', $variables['lottery_summary']);
        $this->assertSame('Regulamento municipal.', $variables['legal_basis']);
    }

    /**
     * @return array{0: Contest, 1: Application}
     */
    private function procedureDataSet(): array
    {
        $program = Program::factory()->create(['name' => 'Programa Alcanena']);
        $contest = Contest::factory()->create([
            'program_id' => $program->id,
            'code' => '01/2026',
            'title' => 'Arrendamento Municipal Acessível de Alcanena',
        ]);
        $candidate = User::factory()->create(['name' => 'Maria Candidata']);
        $jury = User::factory()->create(['name' => 'Presidente do Júri']);
        $application = Application::factory()
            ->submitted()
            ->create([
                'program_id' => $program->id,
                'contest_id' => $contest->id,
                'user_id' => $candidate->id,
            ]);
        $housingUnit = HousingUnit::factory()->create([
            'code' => 'ALC-T2-001',
            'typology' => 'T2',
            'locality' => 'Alcanena',
        ]);
        $contestHousingUnit = ContestHousingUnit::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'housing_unit_id' => $housingUnit->id,
        ]);

        ContestJuryMember::query()->create([
            'contest_id' => $contest->id,
            'user_id' => $jury->id,
            'role_in_jury' => 'Presidente',
            'appointed_at' => now(),
        ]);
        ApplicationPreference::factory()->create([
            'application_id' => $application->id,
            'housing_unit_id' => $housingUnit->id,
            'preference_order' => 1,
        ]);
        ApplicationScore::factory()->create([
            'application_id' => $application->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'user_id' => $candidate->id,
            'total_score' => 42,
            'rank_position' => 1,
        ]);

        $provisionalList = ProvisionalList::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
        ]);
        $provisionalEntry = ProvisionalListEntry::factory()->create([
            'provisional_list_id' => $provisionalList->id,
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'rank_position' => 1,
            'total_score' => 42,
        ]);
        $definitiveList = DefinitiveList::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'provisional_list_id' => $provisionalList->id,
        ]);
        $definitiveEntry = DefinitiveListEntry::factory()->create([
            'definitive_list_id' => $definitiveList->id,
            'provisional_list_entry_id' => $provisionalEntry->id,
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'rank_position' => 1,
            'total_score' => 42,
        ]);

        $hearing = Hearing::factory()->create([
            'provisional_list_id' => $provisionalList->id,
            'application_id' => $application->id,
            'user_id' => $candidate->id,
        ]);
        HearingSubmission::factory()->create([
            'hearing_id' => $hearing->id,
            'application_id' => $application->id,
            'user_id' => $candidate->id,
        ]);
        $complaint = Complaint::factory()->create([
            'provisional_list_id' => $provisionalList->id,
            'provisional_list_entry_id' => $provisionalEntry->id,
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'submitted_at' => now(),
        ]);
        ComplaintDecision::factory()->create([
            'complaint_id' => $complaint->id,
            'application_id' => $application->id,
            'provisional_list_id' => $provisionalList->id,
        ]);

        $draw = LotteryDraw::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'definitive_list_id' => $definitiveList->id,
        ]);
        $participant = LotteryParticipant::factory()->create([
            'lottery_run_id' => $draw->id,
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'definitive_list_entry_id' => $definitiveEntry->id,
        ]);
        LotteryResult::factory()->create([
            'lottery_run_id' => $draw->id,
            'lottery_participant_id' => $participant->id,
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'assigned_contest_housing_unit_id' => $contestHousingUnit->id,
            'assigned_housing_unit_id' => $housingUnit->id,
        ]);

        ProcessConfirmation::factory()->create([
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'contest_id' => $contest->id,
        ]);
        ControlledWithdrawal::factory()->create([
            'application_id' => $application->id,
            'user_id' => $candidate->id,
        ]);
        AdministrativeDecision::factory()->create([
            'application_id' => $application->id,
        ]);

        return [$contest, $application];
    }
}
