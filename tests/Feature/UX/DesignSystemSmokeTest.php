<?php

namespace Tests\Feature\UX;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DesignSystemSmokeTest extends TestCase
{
    public function test_base_ui_components_render_consistent_municipal_classes(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.card>
                <x-ui.section-header title="Indicadores" description="Resumo operacional." />
                <x-ui.status-badge status="completed" />
                <x-ui.status-badge status="warning" label="Atenção" />
                <x-ui.metric-card label="Tarefas" value="4" description="Itens pendentes." href="/tarefas" tone="civic" />
                <x-ui.empty-state title="Sem dados" description="Nada a apresentar." />
                <x-ui.action-button href="/acao" variant="primary">Abrir</x-ui.action-button>
                <x-ui.tabs :tabs="[['key' => 'resumo', 'label' => 'Resumo', 'href' => '#resumo']]" active="resumo" />
            </x-ui.card>
        BLADE);

        $this->assertStringContainsString('mv-card', $html);
        $this->assertStringContainsString('mv-badge', $html);
        $this->assertStringContainsString('Concluído', $html);
        $this->assertStringContainsString('mv-button-primary', $html);
        $this->assertStringContainsString('role="tab"', $html);
        $this->assertStringContainsString('Sem dados', $html);
    }

    public function test_status_badges_cover_required_operational_states(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.status-badge status="pending" />
            <x-ui.status-badge status="overdue" />
            <x-ui.status-badge status="blocked" />
            <x-ui.status-badge status="current" />
            <x-ui.status-badge status="not_applicable" />
        BLADE);

        $this->assertStringContainsString('Pendente', $html);
        $this->assertStringContainsString('Vencido', $html);
        $this->assertStringContainsString('Bloqueado', $html);
        $this->assertStringContainsString('Atual', $html);
        $this->assertStringContainsString('Não aplicável', $html);
    }
}
