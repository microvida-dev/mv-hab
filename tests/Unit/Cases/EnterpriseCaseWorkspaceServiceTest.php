<?php

namespace Tests\Unit\Cases;

use App\Models\Contest;
use App\Models\User;
use App\Services\Cases\EnterpriseCaseWorkspaceService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseCaseWorkspaceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_service_builds_enterprise_case_workspace_payload(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('administrator');
        $contest = Contest::factory()->open()->create();

        $payload = app(EnterpriseCaseWorkspaceService::class)->forCase($user, 'contest', $contest);

        $this->assertSame('contest', $payload['case_type']);
        $this->assertSame('Concurso', $payload['summary']['title']);
        $this->assertNotEmpty($payload['tabs']);
        $this->assertNotEmpty($payload['timeline']);
        $this->assertNotEmpty($payload['checklist']);
        $this->assertArrayHasKey('next_action', $payload);
    }
}
