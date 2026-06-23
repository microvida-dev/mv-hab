<?php

namespace Tests\Feature\Public;

use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\Program;
use App\Models\SimulationSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedSimulatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_run_an_indicative_simulation(): void
    {
        $contest = $this->openContestWithUnit();

        $this->get(route('public.simulator.show'))->assertOk();

        $response = $this->post(route('public.simulator.simulate'), [
            'contest_id' => $contest->id,
            'household_members_count' => 2,
            'adults_count' => 2,
            'dependents_count' => 0,
            'monthly_income' => 1200,
            'housing_status' => 'rented',
            'privacy_notice_accepted' => '1',
        ]);

        $session = SimulationSession::query()->firstOrFail();

        $response->assertRedirect(route('public.simulator.result', ['uuid' => $session->uuid]));
        $this->assertNull($session->user_id);
        $this->get(route('public.simulator.result', ['uuid' => $session->uuid]))
            ->assertOk()
            ->assertSee('Resultado indicativo');
    }

    private function openContestWithUnit(): Contest
    {
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->open()->for($program)->create();
        $housingUnit = HousingUnit::factory()->create(['typology' => 'T1', 'monthly_rent' => 300]);
        ContestHousingUnit::factory()->for($program)->for($contest)->for($housingUnit)->create([
            'typology' => 'T1',
            'monthly_rent' => 300,
        ]);

        return $contest;
    }
}
