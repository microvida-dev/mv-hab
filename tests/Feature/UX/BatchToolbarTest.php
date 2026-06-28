<?php

namespace Tests\Feature\UX;

use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class BatchToolbarTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_batch_toolbar_is_visual_and_does_not_offer_destructive_actions(): void
    {
        $administrator = $this->backofficeUser();

        $this->actingAs($administrator)
            ->withSession($this->verifiedBackofficeSession())
            ->get(route('backoffice.productivity.index'))
            ->assertOk()
            ->assertSee('Seleção em lote')
            ->assertSee('Ações destrutivas indisponíveis')
            ->assertDontSee('Apagar')
            ->assertDontSee('Rejeitar')
            ->assertDontSee('Publicar');
    }
}
