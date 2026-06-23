<?php

namespace Tests\Feature\Backoffice;

use App\Enums\AllocationMethod;
use App\Enums\AllocationStatus;
use App\Enums\DefinitiveListStatus;
use App\Enums\ListEntryStatus;
use App\Enums\ListEntryType;
use App\Enums\LotteryDrawStatus;
use App\Enums\LotteryResultStatus;
use App\Models\Allocation;
use App\Models\AllocationRuleSet;
use App\Models\AllocationRun;
use App\Models\Application;
use App\Models\Contest;
use App\Models\ContestClosure;
use App\Models\ContestHousingUnit;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use App\Models\DrawConvocation;
use App\Models\HousingUnit;
use App\Models\KeyHandoverAppointment;
use App\Models\LotteryDraw;
use App\Models\LotteryResult;
use App\Models\PostDrawReport;
use App\Models\Program;
use App\Models\RankingUpdateRun;
use App\Models\TenantTransition;
use App\Models\User;
use App\Models\WinnerRegistration;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotteryClosureFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_backoffice_executes_lottery_closure_flow_and_candidate_only_sees_own_convocation(): void
    {
        [$administrator, $contest, $allocationRun, $candidate, $otherCandidate] = $this->context();

        $this->actingAs($administrator)
            ->post(route('backoffice.lottery-draws.store'), [
                'allocation_run_id' => $allocationRun->id,
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'location' => 'Sala de testes municipal',
                'seed' => 'SPRINT-25-SEED',
            ])
            ->assertRedirect();

        $draw = LotteryDraw::query()->firstOrFail();

        $this->actingAs($administrator)
            ->post(route('backoffice.lottery-draws.participants.load', $draw))
            ->assertRedirect();
        $this->assertDatabaseCount('lottery_participants', 2);

        $this->actingAs($administrator)
            ->post(route('backoffice.lottery-draws.participants.lock', $draw))
            ->assertRedirect();
        $this->assertNotNull($draw->refresh()->participants_hash);

        $this->actingAs($administrator)
            ->post(route('backoffice.lottery-draws.run', $draw), ['seed' => 'SPRINT-25-SEED'])
            ->assertRedirect();
        $this->assertSame(LotteryDrawStatus::Completed, $draw->refresh()->status);
        $this->assertDatabaseCount('lottery_draw_results', 2);

        $this->actingAs($administrator)
            ->post(route('backoffice.lottery-draws.validate', $draw))
            ->assertRedirect();
        $this->assertSame(LotteryDrawStatus::Validated, $draw->refresh()->status);
        $this->assertSame(2, LotteryResult::query()->where('status', LotteryResultStatus::Validated->value)->count(), 'validated lottery results');

        $winnerResult = LotteryResult::query()->where('selected', true)->firstOrFail();
        $this->actingAs($administrator)
            ->post(route('backoffice.lottery-results.winner.store', $winnerResult))
            ->assertRedirect();
        $this->assertDatabaseCount('winner_registrations', 1);

        $this->actingAs($administrator)
            ->post(route('backoffice.lottery-draws.convocations.generate', $draw), [
                'scheduled_for' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'location' => 'Sala de testes municipal',
            ])
            ->assertRedirect();
        $convocation = DrawConvocation::query()->where('user_id', $candidate->id)->firstOrFail();

        $this->actingAs($candidate)
            ->get(route('candidate.draw-convocations.show', $convocation))
            ->assertOk();

        $this->actingAs($otherCandidate)
            ->get(route('candidate.draw-convocations.show', $convocation))
            ->assertForbidden();

        $participant = $draw->participants()->firstOrFail();
        $this->actingAs($administrator)
            ->post(route('backoffice.lottery-draws.attendance.store', $draw), [
                'application_id' => $participant->application_id,
                'user_id' => $participant->user_id,
                'lottery_participant_id' => $participant->id,
                'status' => 'present',
            ])
            ->assertRedirect();
        $this->assertDatabaseCount('draw_attendances', 1);

        $this->actingAs($administrator)
            ->post(route('backoffice.lottery-draws.ranking.update', $draw))
            ->assertRedirect();
        $this->assertSame(1, RankingUpdateRun::query()->count(), 'ranking update runs');

        $this->actingAs($administrator)
            ->post(route('backoffice.lottery-draws.post-draw-report.generate', $draw))
            ->assertRedirect();
        $this->assertSame(1, PostDrawReport::query()->count(), 'post draw reports');

        $winner = WinnerRegistration::query()->firstOrFail();
        $this->actingAs($administrator)
            ->post(route('backoffice.key-handovers.store'), [
                'winner_registration_id' => $winner->id,
                'scheduled_for' => now()->addWeek()->format('Y-m-d H:i:s'),
                'location' => 'Gabinete municipal de testes',
            ])
            ->assertRedirect();
        $appointment = KeyHandoverAppointment::query()->firstOrFail();

        $this->actingAs($administrator)
            ->post(route('backoffice.key-handovers.complete', $appointment))
            ->assertRedirect();

        $this->actingAs($administrator)
            ->post(route('backoffice.tenant-transitions.run'), [
                'winner_registration_id' => $winner->id,
            ])
            ->assertRedirect();
        $this->assertSame(1, TenantTransition::query()->count(), 'tenant transitions');

        $this->actingAs($administrator)
            ->post(route('backoffice.contests.close', $contest))
            ->assertRedirect();
        $this->assertSame(1, ContestClosure::query()->count(), 'contest closures');
    }

    public function test_guest_and_candidate_cannot_access_backoffice_lottery_draws(): void
    {
        $this->get(route('backoffice.lottery-draws.index'))
            ->assertRedirect(route('login'));

        $candidate = $this->userWithRole('candidate');

        $this->actingAs($candidate)
            ->get(route('backoffice.lottery-draws.index'))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Contest, 2: AllocationRun, 3: User, 4: User}
     */
    private function context(): array
    {
        $administrator = $this->userWithRole('administrator');
        $candidate = $this->userWithRole('candidate');
        $otherCandidate = $this->userWithRole('candidate');
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->for($program)->open()->create();
        $list = DefinitiveList::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'status' => DefinitiveListStatus::Locked->value,
            'generated_by' => $administrator->id,
            'approved_by' => $administrator->id,
            'published_by' => $administrator->id,
            'approved_at' => now(),
            'published_at' => now(),
        ]);
        $ruleSet = AllocationRuleSet::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'allocation_method' => AllocationMethod::Lottery->value,
        ]);
        $allocationRun = AllocationRun::factory()->create([
            'allocation_rule_set_id' => $ruleSet->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'definitive_list_id' => $list->id,
            'allocation_method' => AllocationMethod::Lottery->value,
        ]);

        foreach ([$candidate, $otherCandidate] as $index => $user) {
            $application = Application::factory()->submitted()->create([
                'user_id' => $user->id,
                'program_id' => $program->id,
                'contest_id' => $contest->id,
            ]);
            $entry = DefinitiveListEntry::factory()->create([
                'definitive_list_id' => $list->id,
                'application_id' => $application->id,
                'user_id' => $user->id,
                'entry_type' => ListEntryType::Ranked->value,
                'status' => ListEntryStatus::Ranked->value,
                'rank_position' => $index + 1,
                'total_score' => 100 - $index,
                'public_identifier' => 'PUB-S25-'.$index,
            ]);
            $housingUnit = HousingUnit::factory()->create(['code' => 'S25-HU-'.$index]);
            $contestHousingUnit = ContestHousingUnit::factory()->create([
                'program_id' => $program->id,
                'contest_id' => $contest->id,
                'housing_unit_id' => $housingUnit->id,
            ]);
            Allocation::factory()->create([
                'allocation_run_id' => $allocationRun->id,
                'allocation_rule_set_id' => $ruleSet->id,
                'program_id' => $program->id,
                'contest_id' => $contest->id,
                'definitive_list_id' => $list->id,
                'definitive_list_entry_id' => $entry->id,
                'application_id' => $application->id,
                'user_id' => $user->id,
                'contest_housing_unit_id' => $contestHousingUnit->id,
                'housing_unit_id' => $housingUnit->id,
                'allocation_method' => AllocationMethod::Lottery->value,
                'status' => AllocationStatus::ReadyForContract->value,
                'ready_for_contract_at' => now(),
            ]);
        }

        return [$administrator, $contest, $allocationRun, $candidate, $otherCandidate];
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
