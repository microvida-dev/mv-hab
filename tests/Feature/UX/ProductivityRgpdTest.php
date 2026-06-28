<?php

namespace Tests\Feature\UX;

use App\Models\WorkTask;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ProductivityTestHelpers;
use Tests\TestCase;

class ProductivityRgpdTest extends TestCase
{
    use ProductivityTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_productivity_layer_does_not_render_sensitive_metadata(): void
    {
        $administrator = $this->backofficeUser();
        WorkTask::factory()->assigned($administrator)->create([
            'task_number' => 'WTK-UX06-RGPD',
            'metadata' => [
                'nif' => '123456789',
                'email' => 'cidadao@example.test',
                'telefone' => '900000000',
                'morada' => 'Rua Privada',
                'storage_path' => '/storage/app/private/documento.pdf',
            ],
        ]);

        $this->actingAs($administrator)
            ->withSession($this->verifiedBackofficeSession())
            ->get(route('backoffice.productivity.index'))
            ->assertOk()
            ->assertSee('WTK-UX06-RGPD')
            ->assertDontSee('123456789')
            ->assertDontSee('cidadao@example.test')
            ->assertDontSee('900000000')
            ->assertDontSee('Rua Privada')
            ->assertDontSee('/storage/app/private');
    }
}
