<?php

namespace Tests\Feature;

use App\Enums\ApplicationDeclarationType;
use App\Enums\ApplicationSnapshotType;
use App\Enums\ApplicationStatus;
use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentStatus;
use App\Enums\HousingStatus;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\Contest;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentSubmission;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\Program;
use App\Models\User;
use App\Services\Applications\ApplicationService;
use App\Services\Documents\DocumentChecklistService;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\IncomeSourceSeeder;
use Database\Seeders\RequiredDocumentSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint8ApplicationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_application_pages_require_authentication_role_and_ownership(): void
    {
        $this->seedFoundation();
        $this->get(route('candidate.applications.index'))
            ->assertRedirect(route('login'));

        $administrator = $this->userWithRole('administrator');
        $this->actingAs($administrator)
            ->get(route('candidate.applications.index'))
            ->assertForbidden();

        [$candidate, , , , $contest] = $this->completeCandidateContext();
        $application = $this->createDraft($candidate, $contest);
        $otherCandidate = $this->userWithRole('candidate');

        $this->actingAs($otherCandidate)
            ->get(route('candidate.applications.show', $application))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->get(route('candidate.applications.show', $application))
            ->assertOk()
            ->assertSee($contest->title);
    }

    public function test_candidate_can_only_start_an_application_for_an_open_contest_with_complete_base_data(): void
    {
        [$candidate, , , , $openContest] = $this->completeCandidateContext();

        $this->actingAs($candidate)
            ->get(route('candidate.applications.create', $openContest))
            ->assertOk()
            ->assertSee('Pré-verificação')
            ->assertSee('Criar rascunho');

        $closedContest = Contest::factory()
            ->for($openContest->program)
            ->published()
            ->create([
                'opens_at' => now()->subMonth(),
                'closes_at' => now()->subDay(),
            ]);

        $this->actingAs($candidate)
            ->get(route('candidate.applications.create', $closedContest))
            ->assertNotFound();

        $incompleteCandidate = $this->userWithRole('candidate');
        $this->actingAs($incompleteCandidate)
            ->post(route('candidate.applications.store'), [
                'contest_id' => $openContest->id,
            ])
            ->assertSessionHasErrors('application');
    }

    public function test_candidate_can_create_one_draft_and_critical_fields_are_not_mass_assignable(): void
    {
        [$candidate, $registration, $household, $housing, $contest] = $this->completeCandidateContext();

        $this->actingAs($candidate)
            ->post(route('candidate.applications.store'), [
                'contest_id' => $contest->id,
                'candidate_notes' => 'Nota legítima do candidato.',
                'user_id' => 999999,
                'status' => ApplicationStatus::Eligible->value,
                'application_number' => 'FORJADO',
            ])
            ->assertRedirect();

        $application = Application::query()->firstOrFail();
        $this->assertSame($candidate->id, $application->user_id);
        $this->assertSame($registration->id, $application->adhesion_registration_id);
        $this->assertSame($household->id, $application->household_id);
        $this->assertSame($housing->id, $application->current_housing_situation_id);
        $this->assertSame(ApplicationStatus::Draft, $application->status);
        $this->assertNull($application->application_number);
        $this->assertSame('Nota legítima do candidato.', $application->candidate_notes);
        $this->assertNotEmpty($application->public_id);
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'from_status' => null,
            'to_status' => ApplicationStatus::Draft->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $candidate->id,
            'module' => 'applications',
            'action' => 'create',
        ]);

        $this->actingAs($candidate)
            ->post(route('candidate.applications.store'), [
                'contest_id' => $contest->id,
            ])
            ->assertSessionHasErrors('application');
    }

    public function test_submission_requires_all_declarations_and_all_required_documents(): void
    {
        [$candidate, , , , $contest] = $this->completeCandidateContext();
        $application = $this->createDraft($candidate, $contest);

        $this->actingAs($candidate)
            ->post(route('candidate.applications.submit', $application), [])
            ->assertSessionHasErrors([
                'declaration_accepted',
                'contest_rules_accepted',
                'data_processing_accepted',
                'truthfulness_accepted',
                'data_current_confirmed',
            ]);

        $this->actingAs($candidate)
            ->post(route('candidate.applications.submit', $application), $this->acceptedDeclarations())
            ->assertSessionHasErrors('application');

        $this->assertSame(ApplicationStatus::Draft, $application->fresh()->status);
    }

    public function test_rejected_required_document_blocks_submission(): void
    {
        [$candidate, , , , $contest] = $this->completeCandidateContext();
        $application = $this->createDraft($candidate, $contest);
        $documents = $this->satisfyDocumentChecklist($application);
        $documents->firstOrFail()->forceFill([
            'status' => DocumentStatus::Rejected,
            'rejection_reason' => 'Documento de teste rejeitado.',
        ])->save();

        $this->actingAs($candidate)
            ->post(route('candidate.applications.submit', $application), $this->acceptedDeclarations())
            ->assertSessionHasErrors('application');

        $this->assertSame(ApplicationStatus::Draft, $application->fresh()->status);
    }

    public function test_valid_submission_creates_number_declarations_document_links_snapshots_and_audit(): void
    {
        [$candidate, , , , $contest] = $this->completeCandidateContext();
        $application = $this->createDraft($candidate, $contest);
        $documents = $this->satisfyDocumentChecklist($application);

        $this->actingAs($candidate)
            ->post(route('candidate.applications.submit', $application), $this->acceptedDeclarations())
            ->assertRedirect(route('candidate.applications.receipt', $application));

        $application->refresh();
        $this->assertSame(ApplicationStatus::Submitted, $application->status);
        $this->assertMatchesRegularExpression(
            '/^CAND-'.now()->year.'-[A-Z0-9-]+-\d{6}$/',
            $application->application_number,
        );
        $this->assertNotNull($application->submitted_at);
        $this->assertNotNull($application->locked_at);
        $this->assertTrue($application->declaration_accepted);
        $this->assertTrue($application->contest_rules_accepted);
        $this->assertTrue($application->data_processing_accepted);
        $this->assertTrue($application->truthfulness_accepted);
        $this->assertTrue($application->data_current_confirmed);
        $this->assertSame(count(ApplicationSnapshotType::cases()), $application->snapshots()->count());
        $this->assertSame(count(ApplicationDeclarationType::cases()), $application->declarations()->count());
        $this->assertSame($documents->count(), $application->applicationDocuments()->count());
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'from_status' => ApplicationStatus::Draft->value,
            'to_status' => ApplicationStatus::Submitted->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $candidate->id,
            'module' => 'applications',
            'action' => 'submit',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'applications',
            'action' => 'snapshot',
        ]);

        $documentSnapshot = $application->snapshots()
            ->where('snapshot_type', ApplicationSnapshotType::Documents->value)
            ->firstOrFail();
        $this->assertStringNotContainsString('storage_path', json_encode($documentSnapshot->data));
        $this->assertStringNotContainsString('documents/test/', json_encode($documentSnapshot->data));
    }

    public function test_submitted_application_is_locked_and_receipt_is_private(): void
    {
        [$candidate, , , , $contest] = $this->completeCandidateContext();
        $application = $this->submittedApplication($candidate, $contest);
        $otherCandidate = $this->userWithRole('candidate');

        $this->actingAs($candidate)
            ->patch(route('candidate.applications.update', $application), [
                'candidate_notes' => 'Tentativa posterior à submissão.',
            ])
            ->assertForbidden();

        $this->actingAs($otherCandidate)
            ->get(route('candidate.applications.receipt', $application))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->get(route('candidate.applications.receipt', $application))
            ->assertOk()
            ->assertSee($application->application_number)
            ->assertDontSee('documents/test/');

        $this->actingAs($candidate)
            ->get(route('candidate.applications.print', $application))
            ->assertOk()
            ->assertSee($application->application_number);
    }

    public function test_candidate_can_withdraw_submitted_application_and_history_is_preserved(): void
    {
        [$candidate, , , , $contest] = $this->completeCandidateContext();
        $application = $this->submittedApplication($candidate, $contest);

        $this->actingAs($candidate)
            ->post(route('candidate.applications.withdraw', $application), [
                'reason' => 'Desistência fictícia para teste.',
            ])
            ->assertRedirect(route('candidate.applications.show', $application));

        $application->refresh();
        $this->assertSame(ApplicationStatus::Withdrawn, $application->status);
        $this->assertNotNull($application->withdrawn_at);
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'from_status' => ApplicationStatus::Submitted->value,
            'to_status' => ApplicationStatus::Withdrawn->value,
            'reason' => 'Desistência fictícia para teste.',
        ]);
    }

    public function test_backoffice_can_consult_submitted_applications_but_candidate_cannot_enter_backoffice(): void
    {
        [$candidate, , , , $contest] = $this->completeCandidateContext();
        $application = $this->submittedApplication($candidate, $contest);
        $technician = $this->userWithRole('municipal_technician');

        $this->actingAs($candidate)
            ->get(route('backoffice.applications.index'))
            ->assertForbidden();

        $this->actingAs($technician)
            ->get(route('backoffice.applications.index'))
            ->assertOk()
            ->assertSee($application->application_number);

        $this->actingAs($technician)
            ->get(route('backoffice.applications.show', $application))
            ->assertOk()
            ->assertSee($application->application_number);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $technician->id,
            'module' => 'applications',
            'action' => 'backoffice_view',
        ]);
    }

    public function test_public_contest_exposes_application_cta_only_while_open(): void
    {
        [$candidate, , , , $openContest] = $this->completeCandidateContext();

        $this->actingAs($candidate)
            ->get(route('public.contests.show', $openContest->slug))
            ->assertOk()
            ->assertSee('Iniciar candidatura');

        $closedContest = Contest::factory()
            ->for($openContest->program)
            ->published()
            ->create([
                'opens_at' => now()->subMonth(),
                'closes_at' => now()->subDay(),
            ]);

        $this->actingAs($candidate)
            ->get(route('public.contests.show', $closedContest->slug))
            ->assertOk()
            ->assertDontSee('Iniciar candidatura')
            ->assertSee('A candidatura não está disponível neste momento.');
    }

    private function seedFoundation(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $this->seed(IncomeSourceSeeder::class);
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(RequiredDocumentSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $this->seed(SystemAccessSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function completeCandidateContext(): array
    {
        $this->seedFoundation();
        $candidate = $this->userWithRole('candidate');
        $registration = AdhesionRegistration::factory()->registered()->for($candidate)->create([
            'nif' => 'TEST-APP-'.fake()->unique()->numerify('#####'),
        ]);
        $household = Household::factory()->candidate($registration)->create();
        HouseholdMember::factory()
            ->applicant()
            ->withoutIncome()
            ->create([
                'household_id' => $household->id,
                'adhesion_registration_id' => $registration->id,
                'full_name' => 'Candidato da Sprint 8',
                'birth_date' => today()->subYears(35),
                'nif' => 'MEM-APP-'.fake()->unique()->numerify('#####'),
            ]);
        $housing = CurrentHousingSituation::factory()->create([
            'adhesion_registration_id' => $registration->id,
            'housing_status' => HousingStatus::Rented->value,
            'current_monthly_rent' => 500,
        ]);
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->for($program)->open()->create();

        return [$candidate, $registration, $household, $housing, $contest];
    }

    private function createDraft(User $candidate, Contest $contest): Application
    {
        return app(ApplicationService::class)->createDraft(
            $candidate,
            $contest,
            ['candidate_notes' => 'Rascunho de teste da Sprint 8.'],
        );
    }

    private function satisfyDocumentChecklist(Application $application)
    {
        $items = collect(app(DocumentChecklistService::class)->forApplication($application)['items']);

        return $items->map(function (array $item) use ($application) {
            $target = match ($item['required_for']) {
                DocumentAppliesTo::Household => ['household_id' => $item['target_id']],
                DocumentAppliesTo::HouseholdMember => ['household_member_id' => $item['target_id']],
                DocumentAppliesTo::IncomeRecord => ['income_record_id' => $item['target_id']],
                DocumentAppliesTo::CurrentHousingSituation => ['current_housing_situation_id' => $item['target_id']],
                DocumentAppliesTo::Application => ['application_id' => $item['target_id']],
                default => [],
            };

            return DocumentSubmission::factory()->create([
                'document_type_id' => $item['document_type_id'],
                'required_document_id' => $item['required_document_id'],
                'user_id' => $application->user_id,
                'adhesion_registration_id' => $application->adhesion_registration_id,
                'application_id' => $application->id,
                'status' => DocumentStatus::Submitted->value,
                'submitted_by' => $application->user_id,
                ...$target,
            ]);
        });
    }

    private function submittedApplication(User $candidate, Contest $contest): Application
    {
        $application = $this->createDraft($candidate, $contest);
        $this->satisfyDocumentChecklist($application);

        $this->actingAs($candidate)
            ->post(route('candidate.applications.submit', $application), $this->acceptedDeclarations())
            ->assertRedirect(route('candidate.applications.receipt', $application));

        return $application->fresh();
    }

    private function acceptedDeclarations(): array
    {
        return [
            'declaration_accepted' => '1',
            'contest_rules_accepted' => '1',
            'data_processing_accepted' => '1',
            'truthfulness_accepted' => '1',
            'data_current_confirmed' => '1',
        ];
    }
}
