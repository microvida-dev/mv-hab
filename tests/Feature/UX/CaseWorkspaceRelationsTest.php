<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class CaseWorkspaceRelationsTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_authorized_relations_are_rendered(): void
    {
        $application = Application::factory()->submitted()->create();
        $contract = Contract::factory()->create(['application_id' => $application->id]);

        $this->actingAs($this->userWithRole())
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Relações entre casos')
            ->assertSee('Candidatura');
    }
}
