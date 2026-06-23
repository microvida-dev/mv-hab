<?php

namespace Tests\Feature;

use App\Enums\AdhesionRegistrationStatus;
use App\Models\AdhesionRegistration;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint4AdhesionRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_area_requires_authentication_and_candidate_role(): void
    {
        $this->get(route('candidate.dashboard'))->assertRedirect(route('login'));

        $this->seed(SystemAccessSeeder::class);
        $administrator = User::factory()->create();
        $administrator->assignRole('administrator');

        $this->actingAs($administrator)
            ->get(route('candidate.dashboard'))
            ->assertForbidden();

        $candidate = $this->candidate();

        $this->actingAs($candidate)
            ->get(route('candidate.dashboard'))
            ->assertOk()
            ->assertSee('Área do Candidato');
    }

    public function test_candidate_cannot_access_legacy_backoffice(): void
    {
        $candidate = $this->candidate();

        $this->actingAs($candidate)->get(route('citizens.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.programs.index'))->assertForbidden();
    }

    public function test_candidate_can_access_registration_start_page(): void
    {
        $candidate = $this->candidate();

        $this->actingAs($candidate)
            ->get(route('candidate.registration.show'))
            ->assertRedirect(route('candidate.registration.create'));

        $this->actingAs($candidate)
            ->get(route('candidate.registration.create'))
            ->assertOk()
            ->assertSee('Guardar rascunho');
    }

    public function test_candidate_can_start_incomplete_registration_without_mass_assigning_owner_or_status(): void
    {
        $candidate = $this->candidate();
        $otherCandidate = $this->candidate();

        $response = $this->actingAs($candidate)->post(route('candidate.registration.store'), [
            'full_name' => 'Candidato de Teste',
            'email' => 'candidate@example.test',
            'user_id' => $otherCandidate->id,
            'status' => AdhesionRegistrationStatus::Registered->value,
            'wants_email_notifications' => true,
        ]);

        $registration = AdhesionRegistration::query()->firstOrFail();

        $response->assertRedirect(route('candidate.registration.show'));
        $this->assertSame($candidate->id, $registration->user_id);
        $this->assertSame(AdhesionRegistrationStatus::Incomplete, $registration->status);
        $this->assertDatabaseHas('adhesion_registration_status_histories', [
            'adhesion_registration_id' => $registration->id,
            'from_status' => null,
            'to_status' => AdhesionRegistrationStatus::Incomplete->value,
            'changed_by' => $candidate->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $candidate->id,
            'auditable_type' => $registration->getMorphClass(),
            'auditable_id' => $registration->id,
            'action' => 'create',
        ]);
    }

    public function test_candidate_can_update_only_own_registration(): void
    {
        $candidate = $this->candidate();
        $otherCandidate = $this->candidate();
        $ownRegistration = AdhesionRegistration::factory()->for($candidate)->create([
            'full_name' => 'Titular Correto',
        ]);
        $otherRegistration = AdhesionRegistration::factory()->for($otherCandidate)->create([
            'full_name' => 'Outro Titular Privado',
        ]);

        $this->assertFalse($candidate->can('view', $otherRegistration));
        $this->assertFalse($candidate->can('update', $otherRegistration));

        $this->actingAs($candidate)
            ->get(route('candidate.registration.show'))
            ->assertOk()
            ->assertSee('Titular Correto')
            ->assertDontSee('Outro Titular Privado');

        $this->actingAs($candidate)
            ->patch(route('candidate.registration.update'), [
                ...$this->completeData(),
                'full_name' => 'Titular Atualizado',
                'user_id' => $otherCandidate->id,
                'status' => AdhesionRegistrationStatus::Blocked->value,
            ])
            ->assertRedirect(route('candidate.registration.show'));

        $this->assertSame('Titular Atualizado', $ownRegistration->fresh()->full_name);
        $this->assertSame($candidate->id, $ownRegistration->fresh()->user_id);
        $this->assertSame(AdhesionRegistrationStatus::Incomplete, $ownRegistration->fresh()->status);
        $this->assertSame('Outro Titular Privado', $otherRegistration->fresh()->full_name);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $ownRegistration->id,
            'module' => 'adhesion_registrations',
            'action' => 'update',
        ]);
    }

    public function test_registration_cannot_be_finalized_with_missing_fields_or_without_consents(): void
    {
        $candidate = $this->candidate();
        AdhesionRegistration::factory()->for($candidate)->create([
            'address' => null,
            'accepts_terms' => false,
            'accepts_data_processing' => false,
        ]);

        $this->actingAs($candidate)
            ->post(route('candidate.registration.finalize'))
            ->assertSessionHasErrors(['address', 'accepts_terms', 'accepts_data_processing']);

        $this->assertSame(
            AdhesionRegistrationStatus::Incomplete,
            $candidate->adhesionRegistration->fresh()->status,
        );
    }

    public function test_candidate_must_be_at_least_eighteen_to_finalize(): void
    {
        $candidate = $this->candidate();
        AdhesionRegistration::factory()->for($candidate)->create([
            'birth_date' => today()->subYears(17),
        ]);

        $this->actingAs($candidate)
            ->post(route('candidate.registration.finalize'))
            ->assertSessionHasErrors('birth_date');
    }

    public function test_candidate_can_finalize_complete_registration_with_consent_timestamps(): void
    {
        $candidate = $this->candidate();
        $registration = AdhesionRegistration::factory()->for($candidate)->create();

        $this->actingAs($candidate)
            ->post(route('candidate.registration.finalize'))
            ->assertRedirect(route('candidate.registration.show'));

        $registration->refresh();

        $this->assertSame(AdhesionRegistrationStatus::Registered, $registration->status);
        $this->assertNotNull($registration->submitted_at);
        $this->assertNotNull($registration->accepted_terms_at);
        $this->assertNotNull($registration->accepted_data_processing_at);
        $this->assertDatabaseHas('adhesion_registration_status_histories', [
            'adhesion_registration_id' => $registration->id,
            'from_status' => AdhesionRegistrationStatus::Incomplete->value,
            'to_status' => AdhesionRegistrationStatus::Registered->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $registration->id,
            'module' => 'adhesion_registrations',
            'action' => 'finalize',
        ]);
    }

    public function test_candidate_can_cancel_and_remove_registration_without_applications(): void
    {
        $candidate = $this->candidate();
        $registration = AdhesionRegistration::factory()->registered()->for($candidate)->create();

        $this->actingAs($candidate)
            ->post(route('candidate.registration.cancel'), [
                'reason' => 'Decisão de teste sem dados pessoais.',
            ])
            ->assertRedirect(route('candidate.registration.show'));

        $registration->refresh();
        $this->assertSame(AdhesionRegistrationStatus::Cancelled, $registration->status);
        $this->assertNotNull($registration->cancelled_at);

        $this->actingAs($candidate)
            ->delete(route('candidate.registration.remove'), [
                'confirm_removal' => true,
            ])
            ->assertRedirect(route('candidate.dashboard'));

        $this->assertSoftDeleted('adhesion_registrations', ['id' => $registration->id]);
        $this->assertDatabaseHas('adhesion_registrations', [
            'id' => $registration->id,
            'status' => AdhesionRegistrationStatus::Removed->value,
        ]);
        $this->assertDatabaseHas('adhesion_registration_status_histories', [
            'adhesion_registration_id' => $registration->id,
            'from_status' => AdhesionRegistrationStatus::Cancelled->value,
            'to_status' => AdhesionRegistrationStatus::Removed->value,
        ]);
    }

    public function test_candidate_dashboard_shows_progress_and_available_candidate_modules(): void
    {
        $candidate = $this->candidate();
        AdhesionRegistration::factory()->for($candidate)->create();

        $this->actingAs($candidate)
            ->get(route('candidate.dashboard'))
            ->assertOk()
            ->assertSee('Progresso geral')
            ->assertSee('Agregado')
            ->assertSee('Rendimento mensal');

        $this->actingAs($candidate)
            ->get(route('candidate.applications.index'))
            ->assertOk()
            ->assertSee('As minhas candidaturas')
            ->assertSee('Ver concursos disponíveis');

        $this->actingAs($candidate)
            ->get(route('candidate.documents.index'))
            ->assertOk()
            ->assertSee('Documentos submetidos')
            ->assertSee('Checklist documental');

        $this->actingAs($candidate)
            ->get(route('candidate.notifications.index'))
            ->assertOk()
            ->assertSee('Sem notificações');
    }

    public function test_candidate_account_cannot_be_deleted_while_registration_history_exists(): void
    {
        $candidate = $this->candidate();
        AdhesionRegistration::factory()->for($candidate)->create();

        $this->actingAs($candidate)
            ->delete(route('profile.destroy'))
            ->assertSessionHasErrors('registration', errorBag: 'userDeletion');

        $this->assertDatabaseHas('users', ['id' => $candidate->id]);
    }

    private function candidate(): User
    {
        $this->seed(SystemAccessSeeder::class);

        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        return $candidate;
    }

    private function completeData(): array
    {
        return [
            'full_name' => 'Candidato de Teste',
            'email' => 'candidate@example.test',
            'nif' => 'TEST-00001',
            'birth_date' => today()->subYears(30)->toDateString(),
            'address' => 'Morada de demonstração',
            'postal_code' => '0000-000',
            'city' => 'Localidade de Teste',
            'municipality' => 'Município de Teste',
            'wants_email_notifications' => true,
            'accepts_terms' => true,
            'accepts_data_processing' => true,
        ];
    }
}
