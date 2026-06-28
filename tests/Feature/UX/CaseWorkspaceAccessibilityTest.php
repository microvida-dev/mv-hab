<?php

namespace Tests\Feature\UX;

use App\Models\Contest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class CaseWorkspaceAccessibilityTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_enterprise_case_workspace_has_accessible_navigation_and_search_labels(): void
    {
        $contest = Contest::factory()->open()->create();

        $this->actingAs($this->userWithRole())
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.contests.show', $contest))
            ->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('role="tab"', false)
            ->assertSee('Pesquisar neste processo')
            ->assertSee('Painel do caso');
    }
}
