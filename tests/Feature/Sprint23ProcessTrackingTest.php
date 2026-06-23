<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ControlledWithdrawalStatus;
use App\Enums\OfficialNotificationStatus;
use App\Enums\PublicProcessStatus;
use App\Enums\TimelineEventType;
use App\Enums\TimelineEventVisibility;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\CandidateDataReuseProfile;
use App\Models\FutureApplicationDataReuse;
use App\Models\OfficialNotification;
use App\Models\ProcessTimelineEvent;
use App\Models\User;
use App\Services\ProcessTracking\ApplicationPublicStatusService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint23ProcessTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_sees_own_process_dashboard_and_not_another_candidate_process(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $candidate = $this->userWithRole('candidate');
        $otherCandidate = $this->userWithRole('candidate');
        $application = Application::factory()->submitted()->create(['user_id' => $candidate->id]);
        $process = AdministrativeProcess::factory()->create([
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
        ]);

        $this->actingAs($candidate)
            ->get(route('candidate.processes.show', $process))
            ->assertOk()
            ->assertSee('Candidatura submetida');

        $this->actingAs($otherCandidate)
            ->get(route('candidate.processes.show', $process))
            ->assertForbidden();
    }

    public function test_timeline_hides_internal_events_from_candidate_and_backoffice_can_view_them(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $candidate = $this->userWithRole('candidate');
        $staff = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create(['user_id' => $candidate->id]);
        $process = AdministrativeProcess::factory()->create([
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
        ]);

        ProcessTimelineEvent::factory()->create([
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'event_type' => TimelineEventType::SystemEvent->value,
            'visibility' => TimelineEventVisibility::BackofficeOnly->value,
            'title' => 'Nota técnica interna',
            'occurred_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->get(route('candidate.processes.timeline', $process))
            ->assertOk()
            ->assertDontSee('Nota técnica interna');

        $this->actingAs($staff)
            ->get(route('backoffice.applications.timeline', $application))
            ->assertOk()
            ->assertSee('Nota técnica interna');
    }

    public function test_candidate_notification_center_marks_read_and_archives_own_notifications(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $candidate = $this->userWithRole('candidate');
        $notification = OfficialNotification::factory()->create([
            'user_id' => $candidate->id,
            'status' => OfficialNotificationStatus::Queued->value,
        ]);

        $this->actingAs($candidate)
            ->post(route('candidate.notifications.mark-read', $notification))
            ->assertRedirect();

        $this->assertSame(OfficialNotificationStatus::Read, $notification->refresh()->status);

        $this->actingAs($candidate)
            ->post(route('candidate.notifications.archive', $notification))
            ->assertRedirect(route('candidate.notifications.index'));

        $this->assertSame(OfficialNotificationStatus::Archived, $notification->refresh()->status);
    }

    public function test_controlled_withdrawal_requires_explicit_acknowledgement_and_records_timeline(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $candidate = $this->userWithRole('candidate');
        $application = Application::factory()->submitted()->create([
            'user_id' => $candidate->id,
            'status' => ApplicationStatus::Submitted->value,
        ]);

        $this->actingAs($candidate)
            ->post(route('candidate.controlled-withdrawals.store', $application), [
                'application_id' => $application->id,
                'reason' => 'Motivo fictício para desistência.',
            ])
            ->assertSessionHasErrors('consequence_acknowledged');

        $this->actingAs($candidate)
            ->post(route('candidate.controlled-withdrawals.store', $application), [
                'application_id' => $application->id,
                'reason' => 'Motivo fictício para desistência.',
                'consequence_acknowledged' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('controlled_withdrawals', [
            'application_id' => $application->id,
            'status' => ControlledWithdrawalStatus::PendingConfirmation->value,
        ]);
        $this->assertDatabaseHas('process_timeline_events', [
            'application_id' => $application->id,
            'event_type' => TimelineEventType::WithdrawalRequested->value,
        ]);
    }

    public function test_future_data_reuse_requires_candidate_ownership_and_does_not_copy_document_validity(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $candidate = $this->userWithRole('candidate');
        $otherCandidate = $this->userWithRole('candidate');
        $profile = CandidateDataReuseProfile::factory()->create(['user_id' => $candidate->id]);
        $target = Application::factory()->create(['user_id' => $candidate->id, 'status' => ApplicationStatus::Draft->value]);

        $this->actingAs($otherCandidate)
            ->post(route('candidate.data-reuse.store'), [
                'source_reuse_profile_id' => $profile->id,
                'target_application_id' => $target->id,
                'sections' => ['dados_pessoais'],
            ])
            ->assertNotFound();

        $this->actingAs($candidate)
            ->post(route('candidate.data-reuse.store'), [
                'source_reuse_profile_id' => $profile->id,
                'target_application_id' => $target->id,
                'sections' => ['dados_pessoais', 'agregado'],
            ])
            ->assertRedirect(route('candidate.data-reuse.index'));

        $this->assertDatabaseHas('future_application_data_reuses', [
            'user_id' => $candidate->id,
            'target_application_id' => $target->id,
        ]);
        $reuse = FutureApplicationDataReuse::query()->where('user_id', $candidate->id)->firstOrFail();
        $this->assertStringContainsString('documentos não são copiados', mb_strtolower(implode(' ', $reuse->warnings ?? [])));
    }

    public function test_public_status_service_maps_submitted_application(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $candidate = $this->userWithRole('candidate');
        $application = Application::factory()->submitted()->create(['user_id' => $candidate->id]);

        $this->actingAs($candidate)
            ->get(route('candidate.applications.show', $application))
            ->assertOk();

        $snapshot = app(ApplicationPublicStatusService::class)->refresh($application);

        $this->assertSame(PublicProcessStatus::Submitted, $snapshot->public_status);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
