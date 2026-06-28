<?php

namespace Tests\Unit\Productivity;

use App\Models\MunicipalTeam;
use App\Models\WorkTask;
use App\Services\Productivity\MyWorkService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class MyWorkServiceTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_groups_assigned_and_team_work(): void
    {
        $team = MunicipalTeam::factory()->create();
        $user = $this->backofficeUser('municipal_technician', $team);
        WorkTask::factory()->assigned($user)->create(['municipal_team_id' => $team->id]);
        WorkTask::factory()->create(['municipal_team_id' => $team->id, 'assigned_user_id' => null]);

        $groups = collect(app(MyWorkService::class)->forUser($user))->keyBy('key');

        $this->assertArrayHasKey('assigned', $groups->all());
        $this->assertArrayHasKey('team', $groups->all());
    }
}
