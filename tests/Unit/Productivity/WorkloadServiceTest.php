<?php

namespace Tests\Unit\Productivity;

use App\Models\MunicipalTeam;
use App\Models\WorkTask;
use App\Services\Productivity\WorkloadService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class WorkloadServiceTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_returns_aggregated_workload_without_task_metadata(): void
    {
        $administrator = $this->backofficeUser();
        $team = MunicipalTeam::factory()->create(['name' => 'Equipa Agregada']);
        $technician = $this->backofficeUser('municipal_technician', $team, 'Técnico Agregado');
        WorkTask::factory()->assigned($technician)->create([
            'municipal_team_id' => $team->id,
            'metadata' => ['nif' => '123456789'],
        ]);

        $items = app(WorkloadService::class)->forUser($administrator);

        $this->assertSame('Técnico Agregado', $items[0]['name']);
        $this->assertSame(1, $items[0]['total']);
        $this->assertArrayNotHasKey('metadata', $items[0]);
    }
}
