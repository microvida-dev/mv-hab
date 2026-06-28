<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class ProductivityAuthorizationTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_candidate_does_not_receive_backoffice_productivity(): void
    {
        $candidate = User::factory()->create(['status' => 'active']);
        $candidate->assignRole('candidate');

        $this->actingAs($candidate)
            ->get(route('dashboard'))
            ->assertRedirect(route('candidate.dashboard'));

        $this->actingAs($candidate)
            ->withSession($this->verifiedBackofficeSession())
            ->get(route('backoffice.productivity.index'))
            ->assertForbidden();
    }

    public function test_auditor_receives_read_only_productivity_actions(): void
    {
        $auditor = $this->backofficeUser('auditor', null, 'Auditor UX06');

        $this->actingAs($auditor)
            ->withSession($this->verifiedBackofficeSession())
            ->get(route('backoffice.productivity.index'))
            ->assertOk()
            ->assertSee('Produtividade')
            ->assertDontSee('Apagar');
    }
}
