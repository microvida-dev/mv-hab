<?php

namespace Tests\Unit\UX;

use App\Services\UX\MunicipalLanguageService;
use Tests\TestCase;

class MunicipalLanguageServiceTest extends TestCase
{
    public function test_exposes_municipal_role_priority_and_status_labels(): void
    {
        $service = app(MunicipalLanguageService::class);

        $this->assertSame('Técnico Municipal', $service->roleLabel('municipal_technician'));
        $this->assertSame('Gestor Financeiro', $service->roleLabel('financial_manager'));
        $this->assertSame('Alta', $service->priorityLabel('high'));
        $this->assertSame('Rascunho', $service->statusLabel('draft'));
        $this->assertSame('Em revisão', $service->statusLabel('in_review'));
    }
}
