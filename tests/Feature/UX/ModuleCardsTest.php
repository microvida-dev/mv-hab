<?php

namespace Tests\Feature\UX;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ModuleCardsTest extends TestCase
{
    public function test_module_card_renders_municipal_design_system_structure(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ux.module-card
                title="Concursos"
                description="Programas, critérios e listas municipais."
                href="/backoffice/concursos"
                icon="file"
                status="Ativo"
                metric="4 concursos"
                action-label="Abrir concursos"
            />
        BLADE);

        $this->assertStringContainsString('mv-card-interactive', $html);
        $this->assertStringContainsString('Concursos', $html);
        $this->assertStringContainsString('Programas, critérios e listas municipais.', $html);
        $this->assertStringContainsString('Abrir concursos', $html);
        $this->assertStringContainsString('focus-visible:ring-2', $html);
    }

    public function test_module_card_has_non_authorized_state_without_linking_action(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ux.module-card
                title="Administração"
                description="Acesso condicionado por perfil."
                :authorized="false"
            />
        BLADE);

        $this->assertStringContainsString('Sem autorização', $html);
        $this->assertStringNotContainsString('href=', $html);
    }
}
