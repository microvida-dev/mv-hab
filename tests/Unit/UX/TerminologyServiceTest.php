<?php

namespace Tests\Unit\UX;

use App\Services\UX\TerminologyService;
use Tests\TestCase;

class TerminologyServiceTest extends TestCase
{
    public function test_translates_critical_visible_terms_to_portuguese(): void
    {
        $service = app(TerminologyService::class);

        $this->assertSame('Painel Principal', $service->translate('Dashboard'));
        $this->assertSame('Espaço de Trabalho', $service->translate('Workspace'));
        $this->assertSame('Caixa de Entrada', $service->translate('Inbox'));
        $this->assertSame('Cronologia', $service->translate('Timeline'));
        $this->assertSame('Tarefa', $service->translate('Work Task'));
    }

    public function test_preserves_technical_terms_for_routes_and_rbac(): void
    {
        $service = app(TerminologyService::class);

        $this->assertTrue($service->shouldPreserve('workspace'));
        $this->assertTrue($service->shouldPreserve('permission'));
        $this->assertTrue($service->shouldPreserve('RBAC'));
    }
}
