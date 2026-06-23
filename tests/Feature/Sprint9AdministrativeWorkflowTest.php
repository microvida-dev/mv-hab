<?php

namespace Tests\Feature;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseStatus;
use App\Models\AdhesionRegistration;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\Contest;
use App\Models\CurrentHousingSituation;
use App\Models\Household;
use App\Models\Program;
use App\Models\User;
use App\Services\Administrative\AdministrativeProcessNoteService;
use App\Services\Administrative\AdministrativeProcessService;
use App\Services\Administrative\AdministrativeWorkflowTransitionService;
use App\Services\Administrative\CorrectionRequestService;
use App\Services\Administrative\CorrectionResponseService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Sprint9AdministrativeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_to_administrative_backoffice_is_protected(): void
    {
        $this->seed(SystemAccessSeeder::class);

        $this->get(route('backoffice.administrative-processes.index'))
            ->assertRedirect(route('login'));

        $candidate = $this->userWithRole('candidate');
        $this->actingAs($candidate)
            ->get(route('backoffice.administrative-processes.index'))
            ->assertForbidden();

        $technician = $this->userWithRole('municipal_technician');
        $this->actingAs($technician)
            ->get(route('backoffice.administrative-processes.index'))
            ->assertOk();
    }

    public function test_administrative_process_is_created_once_for_submitted_application_with_history_and_audit(): void
    {
        [$candidate, $application] = $this->submittedApplicationContext();
        $technician = $this->userWithRole('municipal_technician');

        $this->actingAs($technician)
            ->post(route('backoffice.application-intake.create-process', $application))
            ->assertRedirect();

        $process = AdministrativeProcess::query()->firstOrFail();
        $this->assertSame($application->id, $process->application_id);
        $this->assertSame($candidate->id, $process->user_id);
        $this->assertSame(AdministrativeProcessStatus::Received, $process->status);
        $this->assertNotEmpty($process->process_number);
        $this->assertDatabaseHas('administrative_process_status_histories', [
            'administrative_process_id' => $process->id,
            'from_status' => null,
            'to_status' => AdministrativeProcessStatus::Received->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'administrative_processes',
            'action' => 'create',
        ]);

        $this->actingAs($technician)
            ->post(route('backoffice.application-intake.create-process', $application))
            ->assertSessionHasErrors('application');
    }

    public function test_assignment_and_status_transitions_are_controlled(): void
    {
        [, $application] = $this->submittedApplicationContext();
        $technician = $this->userWithRole('municipal_technician');
        $process = app(AdministrativeProcessService::class)->createForApplication($application, $technician);

        $this->expectException(ValidationException::class);
        app(AdministrativeWorkflowTransitionService::class)->transition(
            $process,
            AdministrativeProcessStatus::DocumentReview,
            $technician,
        );
    }

    public function test_correction_request_visibility_and_candidate_response_are_scoped(): void
    {
        [$candidate, $application] = $this->submittedApplicationContext();
        $technician = $this->userWithRole('municipal_technician');
        $process = $this->processReadyForCorrection($application, $technician);

        $draft = app(CorrectionRequestService::class)->create($process, $this->correctionPayload(), $technician);

        $this->actingAs($candidate)
            ->get(route('candidate.correction-requests.show', $draft))
            ->assertForbidden();

        app(CorrectionRequestService::class)->issue($draft, $technician);
        $draft->refresh();

        $this->actingAs($candidate)
            ->get(route('candidate.correction-requests.show', $draft))
            ->assertOk()
            ->assertSee('Pedido de aperfeiçoamento');

        $otherCandidate = $this->userWithRole('candidate');
        $this->actingAs($otherCandidate)
            ->get(route('candidate.correction-requests.show', $draft))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->post(route('candidate.correction-requests.responses.store', $draft), [
                'correction_request_item_id' => $draft->items()->first()->id,
            ])
            ->assertSessionHasErrors('response_text');

        $this->actingAs($candidate)
            ->post(route('candidate.correction-requests.responses.store', $draft), [
                'correction_request_item_id' => $draft->items()->first()->id,
                'response_text' => 'Resposta fictícia do candidato para teste.',
            ])
            ->assertRedirect(route('candidate.correction-requests.show', $draft));

        $this->assertSame(CorrectionRequestStatus::Responded, $draft->fresh()->status);
        $this->assertSame(AdministrativeProcessStatus::CorrectionSubmitted, $process->fresh()->status);
        $this->assertDatabaseHas('correction_responses', [
            'correction_request_id' => $draft->id,
            'user_id' => $candidate->id,
            'status' => CorrectionResponseStatus::Submitted->value,
        ]);
    }

    public function test_response_review_and_admission_decision_prepare_application_for_scoring(): void
    {
        [, $application] = $this->submittedApplicationContext();
        $technician = $this->userWithRole('municipal_technician');
        $process = $this->processReadyForCorrection($application, $technician);
        $request = app(CorrectionRequestService::class)->create($process, $this->correctionPayload(), $technician);
        app(CorrectionRequestService::class)->issue($request, $technician);

        $response = app(CorrectionResponseService::class)->submit($request->refresh(), [
            'correction_request_item_id' => $request->items()->first()->id,
            'response_text' => 'Resposta fictícia aceite.',
        ], $application->user);

        $this->actingAs($technician)
            ->post(route('backoffice.correction-responses.accept', $response), [
                'review_notes' => 'Resposta suficiente para teste.',
            ])
            ->assertRedirect();

        $process->refresh();
        app(AdministrativeWorkflowTransitionService::class)->transition(
            $process,
            AdministrativeProcessStatus::EligibilityReview,
            $technician,
            'Reanálise após aperfeiçoamento.',
        );

        $this->actingAs($technician)
            ->post(route('backoffice.administrative-decisions.store-admission', $process), [
                'summary' => 'Candidatura admitida para classificação em teste.',
                'grounds' => 'Fundamentação administrativa fictícia para teste.',
            ])
            ->assertRedirect();

        $this->assertSame(AdministrativeProcessStatus::AdmittedForScoring, $process->fresh()->status);
        $this->assertTrue(Application::query()->admittedForScoring()->whereKey($application->id)->exists());
        $this->assertDatabaseHas('administrative_decisions', [
            'administrative_process_id' => $process->id,
            'decision_result' => 'admitted_for_scoring',
            'status' => 'approved',
        ]);
    }

    public function test_internal_notes_are_not_visible_to_candidate(): void
    {
        [$candidate, $application] = $this->submittedApplicationContext();
        $technician = $this->userWithRole('municipal_technician');
        $process = app(AdministrativeProcessService::class)->createForApplication($application, $technician);
        app(AdministrativeProcessNoteService::class)->create($process, [
            'visibility' => 'internal',
            'note_type' => 'risk',
            'body' => 'Nota interna que não deve aparecer ao candidato.',
        ], $technician);

        $this->actingAs($candidate)
            ->get(route('candidate.processes.timeline', $process))
            ->assertOk()
            ->assertDontSee('Nota interna que não deve aparecer ao candidato.');
    }

    private function processReadyForCorrection(Application $application, User $technician): AdministrativeProcess
    {
        $service = app(AdministrativeProcessService::class);
        $process = $service->createForApplication($application, $technician);
        $service->assign($process, $technician, $technician);
        $service->startPreliminaryReview($process->refresh(), $technician);
        $service->startDocumentReview($process->refresh(), $technician);
        $service->startEligibilityReview($process->refresh(), $technician);

        return $process->refresh();
    }

    private function correctionPayload(): array
    {
        return [
            'subject' => 'Pedido de aperfeiçoamento fictício',
            'message' => 'Mensagem fictícia para validação do fluxo.',
            'instructions' => 'Responder pela área reservada.',
            'items' => [
                [
                    'title' => 'Esclarecimento necessário',
                    'description' => 'Esclareça a informação indicada.',
                    'issue_type' => 'unclear_information',
                    'required_action' => 'provide_explanation',
                    'is_required' => true,
                ],
            ],
        ];
    }

    private function submittedApplicationContext(): array
    {
        $this->seed(SystemAccessSeeder::class);
        $candidate = $this->userWithRole('candidate');
        $registration = AdhesionRegistration::factory()->registered()->for($candidate)->create([
            'nif' => 'TEST-S9-'.fake()->unique()->numerify('#####'),
        ]);
        $household = Household::factory()->candidate($registration)->create();
        $housing = CurrentHousingSituation::factory()->create([
            'adhesion_registration_id' => $registration->id,
        ]);
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->for($program)->open()->create();

        $application = Application::factory()->submitted()->create([
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $housing->id,
            'status' => ApplicationStatus::Submitted->value,
        ]);

        return [$candidate, $application->fresh()];
    }

    private function userWithRole(string $role): User
    {
        $this->seed(SystemAccessSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
