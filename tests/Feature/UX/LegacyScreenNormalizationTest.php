<?php

namespace Tests\Feature\UX;

use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class LegacyScreenNormalizationTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_enterprise_workspace_provides_normalized_entry_point_to_legacy_detail(): void
    {
        $contract = Contract::factory()->create();

        $this->actingAs($this->userWithRole())
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.contracts.show', $contract))
            ->assertOk()
            ->assertSee('Detalhe legado')
            ->assertSee('Resumo processual')
            ->assertSee('Documentos e anexos');
    }

    public function test_work_task_and_report_legacy_screens_use_portuguese_labels(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.work-tasks.dashboard'))
            ->assertOk()
            ->assertSee('Painel de tarefas')
            ->assertDontSee('Dashboard de tarefas');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.index'))
            ->assertOk()
            ->assertSee('Painel operacional')
            ->assertSee('Painel executivo')
            ->assertDontSee('Dashboard operacional')
            ->assertDontSee('Dashboard executivo');
    }
}
