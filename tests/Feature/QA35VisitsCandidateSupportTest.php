<?php

namespace Tests\Feature;

use App\Enums\TicketCategory;
use App\Enums\VisitCancellationReason;
use App\Enums\VisitStatus;
use App\Models\Contest;
use App\Models\HousingUnit;
use App\Models\HousingVisit;
use App\Models\MunicipalTeam;
use App\Models\Program;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use App\Models\WorkTask;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QA35VisitsCandidateSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_visit_flow_creates_idempotent_work_task_and_preserves_history(): void
    {
        $this->seedAccess();
        $candidate = $this->userWithRole('candidate');
        $staff = $this->userWithRole('support_agent', 'Atendimento');
        $contest = Contest::factory()->for(Program::factory()->published())->open()->create();
        $housingUnit = HousingUnit::factory()->publiclyVisible()->create();
        $availability = VisitAvailability::factory()->create([
            'contest_id' => $contest->id,
            'housing_unit_id' => $housingUnit->id,
            'staff_user_id' => $staff->id,
        ]);
        $firstSlot = $this->slot($availability, $contest, $housingUnit, $staff, 5);
        $secondSlot = $this->slot($availability, $contest, $housingUnit, $staff, 6);

        $this->actingAs($candidate)
            ->post(route('candidate.visits.store'), [
                'visit_slot_id' => $firstSlot->id,
                'contest_id' => $contest->id,
                'housing_unit_id' => $housingUnit->id,
                'candidate_notes' => 'Pedido sintético de visita.',
            ])
            ->assertRedirect();

        $visit = HousingVisit::query()->firstOrFail();

        $this->assertSame($candidate->id, $visit->candidate_user_id);
        $this->assertSame(VisitStatus::PendingConfirmation, $visit->status);
        $this->assertDatabaseHas('housing_visit_status_histories', [
            'housing_visit_id' => $visit->id,
            'to_status' => VisitStatus::PendingConfirmation->value,
        ]);

        $task = WorkTask::query()
            ->where('type', WorkTask::TYPE_VISIT_SCHEDULE)
            ->where('related_type', $visit->getMorphClass())
            ->where('related_id', $visit->id)
            ->firstOrFail();

        $this->assertSame('housing_visit:'.$visit->id, $task->source);
        $this->assertSame('Atendimento', $task->municipalTeam?->name);
        $this->assertSame($staff->id, $task->assigned_user_id);
        $this->assertNotNull($task->due_at);
        $this->assertArrayNotHasKey('candidate_notes', $task->metadata ?? []);

        $this->from(route('candidate.visits.create'))
            ->actingAs($candidate)
            ->post(route('candidate.visits.store'), [
                'visit_slot_id' => $firstSlot->id,
                'contest_id' => $contest->id,
                'housing_unit_id' => $housingUnit->id,
            ])
            ->assertSessionHasErrors('visit_slot_id');

        $this->from(route('candidate.visits.reschedule', $visit))
            ->actingAs($candidate)
            ->post(route('candidate.visits.reschedule.store', $visit), [
                'new_visit_slot_id' => $secondSlot->id,
            ])
            ->assertSessionHasErrors('reason');

        $this->actingAs($candidate)
            ->post(route('candidate.visits.reschedule.store', $visit), [
                'new_visit_slot_id' => $secondSlot->id,
                'reason' => 'Motivo sintético de reagendamento.',
            ])
            ->assertRedirect();

        $this->assertSame(VisitStatus::Rescheduled, $visit->refresh()->status);
        $this->assertSame(1, WorkTask::query()->where('source', 'housing_visit:'.$visit->id)->count());
        $this->assertDatabaseHas('housing_visit_status_histories', [
            'housing_visit_id' => $visit->id,
            'to_status' => VisitStatus::Rescheduled->value,
            'reason' => 'Motivo sintético de reagendamento.',
        ]);

        $this->actingAs($candidate)
            ->post(route('candidate.visits.cancel', $visit), [
                'cancellation_reason' => VisitCancellationReason::CandidateUnavailable->value,
                'cancellation_notes' => 'Motivo sintético de cancelamento.',
            ])
            ->assertRedirect();

        $this->assertSame(VisitStatus::CancelledByCandidate, $visit->refresh()->status);
    }

    public function test_visit_to_non_public_housing_unit_is_blocked(): void
    {
        $this->seedAccess();
        $candidate = $this->userWithRole('candidate');
        $staff = $this->userWithRole('support_agent', 'Atendimento');
        $housingUnit = HousingUnit::factory()->create(['is_public' => false]);
        $availability = VisitAvailability::factory()->create([
            'housing_unit_id' => $housingUnit->id,
            'staff_user_id' => $staff->id,
        ]);
        $slot = $this->slot($availability, null, $housingUnit, $staff, 5);

        $this->from(route('candidate.visits.create'))
            ->actingAs($candidate)
            ->post(route('candidate.visits.store'), [
                'visit_slot_id' => $slot->id,
                'housing_unit_id' => $housingUnit->id,
            ])
            ->assertSessionHasErrors('housing_unit_id');

        $this->assertDatabaseCount('housing_visits', 0);
    }

    public function test_support_ticket_sensitive_categories_are_available_for_routing(): void
    {
        $this->seedAccess();
        $candidate = $this->userWithRole('candidate');

        $this->actingAs($candidate)
            ->post(route('candidate.support-tickets.store'), [
                'category' => TicketCategory::Payment->value,
                'subject' => 'Dúvida sintética sobre pagamento',
                'description' => 'Mensagem sintética com detalhe suficiente para validação.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $candidate->id,
            'category' => TicketCategory::Payment->value,
        ]);
    }

    private function seedAccess(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $this->seed(MunicipalTeamSeeder::class);
    }

    private function userWithRole(string $role, ?string $teamName = null): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        if ($teamName !== null) {
            MunicipalTeam::query()->where('name', $teamName)->firstOrFail()->members()->syncWithoutDetaching([
                $user->id => ['joined_at' => now(), 'role_in_team' => $teamName],
            ]);
        }

        return $user;
    }

    private function slot(VisitAvailability $availability, ?Contest $contest, HousingUnit $housingUnit, User $staff, int $days): VisitSlot
    {
        $startsAt = now()->addDays($days)->setTime(10, 0);

        return VisitSlot::factory()->create([
            'visit_availability_id' => $availability->id,
            'contest_id' => $contest?->id,
            'housing_unit_id' => $housingUnit->id,
            'staff_user_id' => $staff->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'capacity' => 2,
            'booked_count' => 0,
        ]);
    }
}
