<?php

namespace Tests\Feature;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationScoreStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ComplaintDecisionResult;
use App\Enums\ComplaintStatus;
use App\Enums\DefinitiveListStatus;
use App\Enums\HearingType;
use App\Enums\ListEntryStatus;
use App\Enums\ListPublicationChannel;
use App\Enums\ListPublicationStatus;
use App\Enums\ProvisionalListStatus;
use App\Enums\RankingSnapshotStatus;
use App\Models\AdhesionRegistration;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationScore;
use App\Models\Contest;
use App\Models\CurrentHousingSituation;
use App\Models\Household;
use App\Models\Program;
use App\Models\ProvisionalList;
use App\Models\RankingEntry;
use App\Models\RankingSnapshot;
use App\Models\ScoringRun;
use App\Models\User;
use App\Services\Lists\DefinitiveListService;
use App\Services\Lists\ProvisionalListService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint11ListsComplaintsHearingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_backoffice_lists_are_protected_by_role_and_permission(): void
    {
        $this->get(route('backoffice.lists.provisional.index'))
            ->assertRedirect(route('login'));

        $candidate = $this->userWithRole('candidate');
        $this->actingAs($candidate)
            ->get(route('backoffice.lists.provisional.index'))
            ->assertForbidden();

        $technician = $this->userWithRole('municipal_technician');
        $this->actingAs($technician)
            ->get(route('backoffice.lists.provisional.index'))
            ->assertOk();
    }

    public function test_admin_can_generate_provisional_list_from_internal_ranking_snapshot(): void
    {
        [$administrator, $snapshot, $application] = $this->rankingContext();

        $this->actingAs($administrator)
            ->post(route('backoffice.lists.provisional.store'), [
                'ranking_snapshot_id' => $snapshot->id,
                'title' => 'Lista provisória de teste',
                'anonymization_mode' => 'public_identifier_only',
                'public_visibility' => '1',
                'complaint_period_starts_at' => now()->subHour()->format('Y-m-d H:i:s'),
                'complaint_period_ends_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $list = ProvisionalList::query()->with('entries')->firstOrFail();
        $this->assertSame(ProvisionalListStatus::Draft, $list->status);
        $this->assertSame($snapshot->id, $list->ranking_snapshot_id);
        $this->assertSame($application->id, $list->entries->first()->application_id);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'public_lists',
            'action' => 'provisional_list_generate',
        ]);
    }

    public function test_provisional_list_cannot_be_published_without_approval_and_public_view_is_anonymized(): void
    {
        [$administrator, $snapshot, $application, $candidate] = $this->rankingContext();
        $this->actingAs($administrator);
        $list = app(ProvisionalListService::class)->generateFromSnapshot($this->listPayload($snapshot), $administrator);

        $this->post(route('backoffice.lists.provisional.publish', $list))
            ->assertSessionHasErrors('provisional_list');

        app(ProvisionalListService::class)->approve($list->refresh(), $administrator);
        app(ProvisionalListService::class)->publish($list->refresh(), $administrator);

        $list->refresh();
        $publication = $list->publications()->firstOrFail();
        $entry = $list->entries()->firstOrFail();

        $this->assertSame(ProvisionalListStatus::Published, $list->status);
        $this->assertSame(ListPublicationStatus::Published, $publication->status);
        $this->assertSame(ListPublicationChannel::PublicPortal, $publication->channel);
        $this->assertDatabaseHas('official_notifications', [
            'user_id' => $candidate->id,
            'notification_type' => 'provisional_list_published',
        ]);

        $this->get(route('public.results.show', $publication))
            ->assertOk()
            ->assertSee($entry->public_identifier)
            ->assertDontSee($candidate->name)
            ->assertDontSee($candidate->email)
            ->assertDontSee($application->application_number);
    }

    public function test_candidate_can_submit_own_complaint_during_period_and_cannot_complain_about_other_candidate(): void
    {
        [$administrator, $snapshot, $application, $candidate] = $this->rankingContext();
        $this->actingAs($administrator);
        $list = $this->publishedComplaintOpenList($snapshot, $administrator);
        $entry = $list->entries()->firstOrFail();

        $this->actingAs($candidate)
            ->post(route('candidate.complaints.store'), [
                'provisional_list_id' => $list->id,
                'provisional_list_entry_id' => $entry->id,
                'application_id' => $application->id,
                'subject' => 'Reclamação de teste',
                'grounds' => 'Fundamentos fictícios suficientes para teste.',
                'status' => ComplaintStatus::Accepted->value,
            ])
            ->assertRedirect();

        $complaint = $candidate->complaints()->firstOrFail();
        $this->assertSame(ComplaintStatus::Draft, $complaint->status);

        $this->actingAs($candidate)
            ->post(route('candidate.complaints.submit', $complaint), ['truthfulness_confirmed' => '1'])
            ->assertRedirect();
        $this->assertSame(ComplaintStatus::Submitted, $complaint->fresh()->status);

        $otherCandidate = $this->userWithRole('candidate');
        $this->actingAs($otherCandidate)
            ->post(route('candidate.complaints.store'), [
                'provisional_list_id' => $list->id,
                'provisional_list_entry_id' => $entry->id,
                'application_id' => $application->id,
                'subject' => 'Tentativa indevida',
                'grounds' => 'Fundamentos fictícios suficientes para teste.',
            ])
            ->assertNotFound();
    }

    public function test_backoffice_can_request_information_decide_complaint_and_generate_definitive_list(): void
    {
        [$administrator, $snapshot, $application, $candidate] = $this->rankingContext();
        $this->actingAs($administrator);
        $list = $this->publishedComplaintOpenList($snapshot, $administrator);
        $complaint = $this->submittedComplaint($list, $application, $candidate);

        $this->actingAs($administrator);
        $this->post(route('backoffice.complaints.mark-received', $complaint))->assertRedirect();
        $this->post(route('backoffice.complaints.start-review', $complaint->fresh()))->assertRedirect();
        $this->post(route('backoffice.complaint-decisions.store', $complaint->fresh()), [
            'decision_result' => ComplaintDecisionResult::Accepted->value,
            'summary' => 'Reclamação aceite para refletir na lista definitiva.',
            'grounds' => 'Fundamentação administrativa fictícia.',
            'requires_list_update' => '1',
            'candidate_visible' => '1',
        ])->assertRedirect();

        $decision = $complaint->fresh()->decision;
        $this->post(route('backoffice.complaint-decisions.approve', $decision))->assertRedirect();
        $this->assertSame(ComplaintStatus::Accepted, $complaint->fresh()->status);

        app(ProvisionalListService::class)->closeComplaintPeriod($list->fresh(), $administrator);
        $definitive = app(DefinitiveListService::class)->generateFromProvisional($list->fresh(), [
            'title' => 'Lista definitiva de teste',
            'public_visibility' => true,
            'anonymization_mode' => 'public_identifier_only',
        ], $administrator);

        $entry = $definitive->entries()->firstOrFail();
        $this->assertTrue($entry->changed_after_complaint);
        $this->assertSame(ListEntryStatus::ChangedAfterComplaint, $entry->status);
        $this->assertDatabaseHas('list_change_logs', [
            'application_id' => $application->id,
            'change_type' => 'complaint_effect',
        ]);
    }

    public function test_candidate_can_submit_own_hearing_submission_and_ready_for_allocation_scope_uses_locked_definitive_list(): void
    {
        [$administrator, $snapshot, $application, $candidate] = $this->rankingContext();
        $this->actingAs($administrator);
        $list = $this->publishedComplaintOpenList($snapshot, $administrator);
        app(ProvisionalListService::class)->closeComplaintPeriod($list->fresh(), $administrator);
        $definitive = app(DefinitiveListService::class)->generateFromProvisional($list->fresh(), [
            'title' => 'Lista definitiva sem reclamações',
            'public_visibility' => false,
            'anonymization_mode' => 'public_identifier_only',
        ], $administrator);
        app(DefinitiveListService::class)->approve($definitive->fresh(), $administrator);
        app(DefinitiveListService::class)->publish($definitive->fresh(), $administrator);
        app(DefinitiveListService::class)->lock($definitive->fresh(), $administrator);

        $this->assertSame(DefinitiveListStatus::Locked, $definitive->fresh()->status);
        $this->assertTrue(Application::query()->readyForAllocation()->whereKey($application->id)->exists());

        $this->post(route('backoffice.hearings.store'), [
            'application_id' => $application->id,
            'hearing_type' => HearingType::IntentionToChangeRanking->value,
            'subject' => 'Audiência de teste',
            'message' => 'Mensagem de audiência fictícia.',
            'grounds' => 'Fundamentos fictícios.',
            'deadline_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'candidate_visible' => '1',
        ])->assertRedirect();
        $hearing = $application->hearings()->firstOrFail();
        $this->post(route('backoffice.hearings.issue', $hearing))->assertRedirect();

        $this->actingAs($candidate)
            ->post(route('candidate.hearings.submit.store', $hearing->fresh()), [
                'submission_text' => 'Pronúncia fictícia própria do candidato.',
            ])
            ->assertRedirect();

        $otherCandidate = $this->userWithRole('candidate');
        $this->actingAs($otherCandidate)
            ->post(route('candidate.hearings.submit.store', $hearing->fresh()), [
                'submission_text' => 'Tentativa indevida de pronúncia.',
            ])
            ->assertSessionHasErrors('hearing');
    }

    private function publishedComplaintOpenList(RankingSnapshot $snapshot, User $administrator): ProvisionalList
    {
        $list = app(ProvisionalListService::class)->generateFromSnapshot($this->listPayload($snapshot), $administrator);
        app(ProvisionalListService::class)->approve($list->fresh(), $administrator);
        app(ProvisionalListService::class)->publish($list->fresh(), $administrator);
        app(ProvisionalListService::class)->openComplaintPeriod($list->fresh(), $administrator);

        return $list->fresh();
    }

    private function submittedComplaint(ProvisionalList $list, Application $application, User $candidate)
    {
        $entry = $list->entries()->firstOrFail();
        $this->actingAs($candidate)
            ->post(route('candidate.complaints.store'), [
                'provisional_list_id' => $list->id,
                'provisional_list_entry_id' => $entry->id,
                'application_id' => $application->id,
                'subject' => 'Reclamação para decisão',
                'grounds' => 'Fundamentos fictícios suficientes para decisão.',
            ])->assertRedirect();

        $complaint = $candidate->complaints()->firstOrFail();
        $this->post(route('candidate.complaints.submit', $complaint), ['truthfulness_confirmed' => '1'])->assertRedirect();

        return $complaint->fresh();
    }

    private function listPayload(RankingSnapshot $snapshot): array
    {
        return [
            'ranking_snapshot_id' => $snapshot->id,
            'title' => 'Lista provisória de teste',
            'anonymization_mode' => 'public_identifier_only',
            'public_visibility' => true,
            'complaint_period_starts_at' => now()->subHour(),
            'complaint_period_ends_at' => now()->addWeek(),
        ];
    }

    private function rankingContext(): array
    {
        $administrator = $this->userWithRole('administrator');
        $candidate = $this->userWithRole('candidate', [
            'name' => 'Candidato Sensível',
            'email' => 'sensivel-sprint11@example.test',
        ]);
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->for($program)->open()->create();
        $registration = AdhesionRegistration::factory()->registered()->for($candidate)->create([
            'nif' => 'TEST-S11-'.fake()->unique()->numerify('#####'),
        ]);
        $household = Household::factory()->candidate($registration)->create();
        $housing = CurrentHousingSituation::factory()->create(['adhesion_registration_id' => $registration->id]);
        $application = Application::factory()->submitted()->create([
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $housing->id,
            'status' => ApplicationStatus::Submitted->value,
        ]);
        AdministrativeProcess::factory()->create([
            'application_id' => $application->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'user_id' => $candidate->id,
            'status' => AdministrativeProcessStatus::AdmittedForScoring->value,
            'admitted_for_scoring_at' => now(),
        ]);
        $run = ScoringRun::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'started_by' => $administrator->id,
        ]);
        $score = ApplicationScore::factory()->create([
            'scoring_run_id' => $run->id,
            'application_id' => $application->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'user_id' => $candidate->id,
            'status' => ApplicationScoreStatus::Locked->value,
            'total_score' => 42,
            'rank_position' => 1,
            'locked_at' => now(),
        ]);
        $snapshot = RankingSnapshot::factory()->create([
            'scoring_run_id' => $run->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'status' => RankingSnapshotStatus::Internal->value,
            'generated_by' => $administrator->id,
        ]);
        RankingEntry::factory()->create([
            'ranking_snapshot_id' => $snapshot->id,
            'application_score_id' => $score->id,
            'application_id' => $application->id,
            'rank_position' => 1,
            'total_score' => 42,
        ]);

        return [$administrator, $snapshot->fresh('entries'), $application->fresh(), $candidate];
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }
}
