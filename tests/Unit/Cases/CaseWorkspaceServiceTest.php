<?php

namespace Tests\Unit\Cases;

use App\Models\Application;
use App\Models\User;
use App\Services\Cases\CaseWorkspaceService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseWorkspaceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_service_builds_application_case_workspace_payload(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('municipal_technician');
        $application = Application::factory()->submitted()->create();

        $payload = app(CaseWorkspaceService::class)->forApplication($user, $application);

        $this->assertSame('application', $payload['case_type']);
        $this->assertSame('Candidatura', $payload['summary']['title']);
        $this->assertNotEmpty($payload['tabs']);
        $this->assertNotEmpty($payload['checklist']);
        $this->assertNotEmpty($payload['progress']);
        $this->assertArrayHasKey('next_action', $payload);
    }
}
