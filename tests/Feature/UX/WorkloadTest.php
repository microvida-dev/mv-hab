<?php

namespace Tests\Feature\UX;

use App\Models\MunicipalTeam;
use App\Models\WorkTask;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class WorkloadTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_workload_shows_only_aggregated_operational_counts(): void
    {
        $administrator = $this->backofficeUser();
        $team = MunicipalTeam::factory()->create(['name' => 'Gabinete UX06']);
        $technician = $this->backofficeUser('municipal_technician', $team, 'Joana Técnica');
        WorkTask::factory()->assigned($technician)->create([
            'municipal_team_id' => $team->id,
            'metadata' => ['nif' => '123456789', 'morada' => 'Rua Privada'],
            'due_at' => now()->subDay(),
        ]);

        $this->actingAs($administrator)
            ->withSession($this->verifiedBackofficeSession())
            ->get(route('backoffice.productivity.index'))
            ->assertOk()
            ->assertSee('Carga operacional')
            ->assertSee('Joana Técnica')
            ->assertSee('Gabinete UX06')
            ->assertDontSee('123456789')
            ->assertDontSee('Rua Privada');
    }
}
