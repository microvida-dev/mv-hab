<?php

namespace Tests\Feature\UX;

use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class ProductivityAccessibilityTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_productivity_components_have_accessible_landmarks_and_focus_states(): void
    {
        $administrator = $this->backofficeUser();

        $this->actingAs($administrator)
            ->withSession($this->verifiedBackofficeSession())
            ->get(route('backoffice.productivity.index'))
            ->assertOk()
            ->assertSee('aria-live="polite"', false)
            ->assertSee('focus:ring-2', false)
            ->assertSee('Centro de Trabalho')
            ->assertSee('Seleção em lote');
    }
}
