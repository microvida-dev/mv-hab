<?php

namespace Tests\Feature\UX;

use App\Models\Contest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class CaseWorkspaceChecklistTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_contextual_checklist_is_visual_and_present(): void
    {
        $contest = Contest::factory()->open()->create();

        $this->actingAs($this->userWithRole())
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.contests.show', $contest))
            ->assertOk()
            ->assertSee('Checklist contextual')
            ->assertSee('Programa definido')
            ->assertSee('Prazos definidos');
    }
}
