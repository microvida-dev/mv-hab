<?php

namespace Tests\Feature\UX;

use App\Models\Contest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class CaseWorkspaceContextSearchTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_contextual_search_uses_authorized_case_sections(): void
    {
        $contest = Contest::factory()->open()->create();

        $this->actingAs($this->userWithRole())
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.contests.show', ['contest' => $contest, 'q' => 'Programa']))
            ->assertOk()
            ->assertSee('Pesquisar neste processo')
            ->assertSee('Programa definido');
    }
}
