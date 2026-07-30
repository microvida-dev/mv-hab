<?php

namespace Tests\Feature;

use App\Enums\TicketCategory;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\CreatesTenantSupportEligibility;
use Tests\TestCase;

class QA35VisitsCandidateSupportTest extends TestCase
{
    use CreatesTenantSupportEligibility, RefreshDatabase;

    public function test_candidate_visit_flow_creates_idempotent_work_task_and_preserves_history(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        config([
            'mvhab.candidate_experience_runtime.legacy_visits' => true,
        ]);

        $this->assertFalse(Route::has('candidate.visits.store'));
        $this->assertFalse(Route::has('candidate.visits.reschedule.store'));
        $this->assertFalse(Route::has('candidate.visits.cancel'));

        $this->actingAs($candidate)
            ->post('/area-candidato/visitas', [
                'visit_slot_id' => 1,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('housing_visits', 0);
        $this->assertDatabaseCount('work_tasks', 0);
    }

    public function test_visit_to_non_public_housing_unit_is_blocked(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $this->assertFalse(Route::has('candidate.visits.create'));
        $this->assertFalse(Route::has('candidate.visits.store'));

        $this->actingAs($candidate)
            ->get('/area-candidato/visitas/agendar')
            ->assertNotFound();

        $this->assertDatabaseCount('housing_visits', 0);
    }

    public function test_support_ticket_sensitive_categories_are_available_for_routing(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');
        $this->enableTenantSupportFor($candidate);

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
}
