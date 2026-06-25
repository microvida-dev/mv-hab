<?php

namespace Tests\Feature;

use App\Enums\InconsistencySeverity;
use App\Enums\InconsistencyType;
use App\Enums\InteractionType;
use App\Enums\MessageVisibility;
use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Enums\VisitCancellationReason;
use App\Enums\VisitStatus;
use App\Models\Application;
use App\Models\ApplicationSimulationInconsistency;
use App\Models\Contest;
use App\Models\ContextualFaq;
use App\Models\ContextualFaqCategory;
use App\Models\HousingVisit;
use App\Models\Program;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Sprint22CandidateSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_books_reschedules_and_cancels_visit_with_ownership_protection(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $candidate = $this->userWithRole('candidate');
        $otherCandidate = $this->userWithRole('candidate');
        $staff = $this->userWithRole('municipal_technician');
        $contest = Contest::factory()->for(Program::factory()->published())->open()->create();
        $availability = VisitAvailability::factory()->create(['contest_id' => $contest->id, 'staff_user_id' => $staff->id]);
        $firstSlot = VisitSlot::factory()->create(['visit_availability_id' => $availability->id, 'contest_id' => $contest->id, 'staff_user_id' => $staff->id]);
        $secondSlot = VisitSlot::factory()->create(['visit_availability_id' => $availability->id, 'contest_id' => $contest->id, 'staff_user_id' => $staff->id]);

        $this->actingAs($candidate)
            ->post(route('candidate.visits.store'), [
                'visit_slot_id' => $firstSlot->id,
                'contest_id' => $contest->id,
                'candidate_notes' => 'Visita fictícia para teste.',
            ])
            ->assertRedirect();

        $visit = HousingVisit::query()->firstOrFail();
        $this->assertSame($candidate->id, $visit->candidate_user_id);
        $this->assertSame(VisitStatus::PendingConfirmation, $visit->status);
        $this->assertSame(1, $firstSlot->refresh()->booked_count);

        $this->actingAs($otherCandidate)
            ->get(route('candidate.visits.show', $visit))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->post(route('candidate.visits.reschedule.store', $visit), [
                'new_visit_slot_id' => $secondSlot->id,
                'reason' => 'Preferência horária fictícia.',
            ])
            ->assertRedirect();

        $visit->refresh();
        $this->assertSame(VisitStatus::Rescheduled, $visit->status);
        $this->assertSame(0, $firstSlot->refresh()->booked_count);
        $this->assertSame(1, $secondSlot->refresh()->booked_count);

        $this->actingAs($candidate)
            ->post(route('candidate.visits.cancel', $visit), [
                'cancellation_reason' => VisitCancellationReason::CandidateUnavailable->value,
                'cancellation_notes' => 'Cancelamento fictício.',
            ])
            ->assertRedirect();

        $this->assertSame(VisitStatus::CancelledByCandidate, $visit->refresh()->status);
        $this->assertSame(0, $secondSlot->refresh()->booked_count);
        $this->assertDatabaseHas('candidate_interactions', [
            'user_id' => $candidate->id,
            'interaction_type' => InteractionType::VisitCancelled->value,
        ]);
    }

    public function test_backoffice_generates_visit_slots_and_updates_visit_status(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $staff = $this->userWithRole('municipal_technician');
        $candidate = $this->userWithRole('candidate');
        $contest = Contest::factory()->for(Program::factory()->published())->open()->create();

        $this->withSession(['mfa.verified_at' => now()])
            ->actingAs($staff)
            ->post(route('backoffice.visit-availabilities.store'), [
                'contest_id' => $contest->id,
                'staff_user_id' => $staff->id,
                'title' => 'Disponibilidade de teste',
                'starts_at' => now()->addDays(4)->setTime(9, 0)->toDateTimeString(),
                'ends_at' => now()->addDays(4)->setTime(10, 0)->toDateTimeString(),
                'slot_duration_minutes' => 30,
                'capacity_per_slot' => 1,
                'buffer_minutes' => 0,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $availability = VisitAvailability::query()->firstOrFail();

        $this->withSession(['mfa.verified_at' => now()])
            ->actingAs($staff)
            ->post(route('backoffice.visit-availabilities.slots.generate', $availability), [
                'location' => 'Edifício municipal',
            ])
            ->assertRedirect();

        $slot = VisitSlot::query()->firstOrFail();
        $visit = HousingVisit::factory()->create([
            'visit_slot_id' => $slot->id,
            'contest_id' => $contest->id,
            'candidate_user_id' => $candidate->id,
            'staff_user_id' => $staff->id,
            'starts_at' => $slot->starts_at,
            'ends_at' => $slot->ends_at,
        ]);

        $this->withSession(['mfa.verified_at' => now()])
            ->actingAs($staff)
            ->post(route('backoffice.housing-visits.confirm', $visit))
            ->assertRedirect();

        $this->assertSame(VisitStatus::Confirmed, $visit->refresh()->status);

        $this->withSession(['mfa.verified_at' => now()])
            ->actingAs($staff)
            ->post(route('backoffice.housing-visits.complete', $visit), ['staff_notes' => 'Concluída em teste.'])
            ->assertRedirect();

        $this->assertSame(VisitStatus::Completed, $visit->refresh()->status);
    }

    public function test_candidate_support_ticket_conversation_hides_internal_messages(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $candidate = $this->userWithRole('candidate');
        $otherCandidate = $this->userWithRole('candidate');
        $staff = $this->userWithRole('municipal_technician');

        $this->actingAs($candidate)
            ->post(route('candidate.support-tickets.store'), [
                'category' => TicketCategory::Application->value,
                'subject' => 'Dúvida sobre candidatura',
                'description' => 'Mensagem fictícia com detalhe suficiente.',
            ])
            ->assertRedirect();

        $ticket = SupportTicket::query()->firstOrFail();
        $this->assertSame($candidate->id, $ticket->user_id);
        $this->assertSame(TicketStatus::Open, $ticket->status);

        $this->actingAs($otherCandidate)
            ->get(route('candidate.support-tickets.show', $ticket))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->post(route('candidate.support-ticket-messages.store', $ticket), [
                'message' => 'Resposta pública do candidato.',
                'visibility' => MessageVisibility::InternalOnly->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'message' => 'Resposta pública do candidato.',
            'visibility' => MessageVisibility::CandidateVisible->value,
        ]);

        $this->withSession(['mfa.verified_at' => now()])
            ->actingAs($staff)
            ->post(route('backoffice.support-ticket-messages.store', $ticket), [
                'message' => 'Nota interna dos serviços.',
                'visibility' => MessageVisibility::InternalOnly->value,
            ])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->get(route('candidate.support-tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('Nota interna dos serviços.');
    }

    public function test_contextual_faq_view_is_recorded_and_backoffice_can_resolve_inconsistency(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $candidate = $this->userWithRole('candidate');
        $staff = $this->userWithRole('municipal_technician');
        $category = ContextualFaqCategory::factory()->create();
        $faq = ContextualFaq::factory()->create([
            'contextual_faq_category_id' => $category->id,
            'context_key' => 'application_draft',
            'question' => 'Como rever a candidatura?',
        ]);

        $this->actingAs($candidate)
            ->get(route('candidate.contextual-faq.index', ['viewed' => $faq->id]))
            ->assertOk()
            ->assertSee('Como rever a candidatura?');

        $this->assertDatabaseHas('candidate_interactions', [
            'user_id' => $candidate->id,
            'interaction_type' => InteractionType::FaqViewed->value,
        ]);

        $application = Application::factory()->create(['user_id' => $candidate->id]);
        $inconsistency = ApplicationSimulationInconsistency::factory()->create([
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'type' => InconsistencyType::IncomeChanged->value,
            'severity' => InconsistencySeverity::Warning->value,
        ]);

        $this->withSession(['mfa.verified_at' => now()])
            ->actingAs($staff)
            ->post(route('backoffice.application-inconsistencies.resolve', $inconsistency), [
                'resolution_note' => 'Revisto em teste.',
            ])
            ->assertRedirect();

        $this->assertTrue($inconsistency->refresh()->is_resolved);
        $this->assertSame($staff->id, $inconsistency->resolved_by);
    }

    public function test_support_ticket_attachment_download_is_private_and_authorized(): void
    {
        Storage::fake('local');
        $this->seed(SystemAccessSeeder::class);
        $candidate = $this->userWithRole('candidate');
        $otherCandidate = $this->userWithRole('candidate');
        $ticket = SupportTicket::factory()->create(['user_id' => $candidate->id]);
        $attachment = SupportTicketAttachment::factory()->create([
            'support_ticket_id' => $ticket->id,
            'uploaded_by' => $candidate->id,
            'path' => 'support-tickets/demo/documento-ficticio.pdf',
        ]);
        Storage::disk('local')->put($attachment->path, 'conteudo ficticio');

        $this->actingAs($otherCandidate)
            ->get(route('candidate.support-ticket-attachments.download', $attachment))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->get(route('candidate.support-ticket-attachments.download', $attachment))
            ->assertOk();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
