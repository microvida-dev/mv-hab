<?php

namespace Tests\Feature\Backoffice;

use App\Enums\VisitStatus;
use App\Models\HousingVisit;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HousingVisitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_backoffice_completion_requires_internal_note_and_creates_audit(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $staff = $this->userWithRole('municipal_technician');
        $visit = HousingVisit::factory()->create([
            'status' => VisitStatus::Confirmed->value,
            'staff_user_id' => $staff->id,
        ]);

        $this->withSession(['mfa.verified_at' => now()])
            ->from(route('backoffice.housing-visits.show', $visit))
            ->actingAs($staff)
            ->post(route('backoffice.housing-visits.complete', $visit), [])
            ->assertSessionHasErrors('staff_notes');

        $this->withSession(['mfa.verified_at' => now()])
            ->actingAs($staff)
            ->post(route('backoffice.housing-visits.complete', $visit), [
                'staff_notes' => 'Visita realizada em cenário sintético.',
            ])
            ->assertRedirect();

        $this->assertSame(VisitStatus::Completed, $visit->refresh()->status);
        $this->assertDatabaseHas('housing_visit_status_histories', [
            'housing_visit_id' => $visit->id,
            'to_status' => VisitStatus::Completed->value,
            'notes' => 'Visita realizada em cenário sintético.',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $visit->getMorphClass(),
            'auditable_id' => $visit->id,
            'module' => 'visits',
            'action' => 'housing_visit',
        ]);
    }

    public function test_backoffice_rejection_requires_reason(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $staff = $this->userWithRole('municipal_technician');
        $visit = HousingVisit::factory()->create(['staff_user_id' => $staff->id]);

        $this->withSession(['mfa.verified_at' => now()])
            ->from(route('backoffice.housing-visits.show', $visit))
            ->actingAs($staff)
            ->post(route('backoffice.housing-visits.reject', $visit), [])
            ->assertSessionHasErrors('reason');

        $this->withSession(['mfa.verified_at' => now()])
            ->actingAs($staff)
            ->post(route('backoffice.housing-visits.reject', $visit), [
                'reason' => 'Motivo sintético de recusa.',
            ])
            ->assertRedirect();

        $this->assertSame(VisitStatus::Rejected, $visit->refresh()->status);
        $this->assertDatabaseHas('housing_visit_status_histories', [
            'housing_visit_id' => $visit->id,
            'to_status' => VisitStatus::Rejected->value,
            'notes' => 'Motivo sintético de recusa.',
        ]);
    }

    public function test_backoffice_can_mark_visit_no_show_with_required_note(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $staff = $this->userWithRole('municipal_technician');
        $visit = HousingVisit::factory()->create([
            'status' => VisitStatus::Confirmed->value,
            'staff_user_id' => $staff->id,
        ]);

        $this->withSession(['mfa.verified_at' => now()])
            ->from(route('backoffice.housing-visits.show', $visit))
            ->actingAs($staff)
            ->post(route('backoffice.housing-visits.no-show', $visit), [])
            ->assertSessionHasErrors('staff_notes');

        $this->withSession(['mfa.verified_at' => now()])
            ->actingAs($staff)
            ->post(route('backoffice.housing-visits.no-show', $visit), [
                'staff_notes' => 'Falta de comparência em cenário sintético.',
            ])
            ->assertRedirect();

        $this->assertSame(VisitStatus::Missed, $visit->refresh()->status);
        $this->assertDatabaseHas('housing_visit_status_histories', [
            'housing_visit_id' => $visit->id,
            'to_status' => VisitStatus::Missed->value,
            'notes' => 'Falta de comparência em cenário sintético.',
        ]);
        $this->assertDatabaseHas('candidate_interactions', [
            'user_id' => $visit->candidate_user_id,
            'interaction_type' => 'visit_no_show',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
