<?php

namespace Tests\Unit\Regulatory;

use Tests\TestCase;

class AlcanenaRegulatoryMappingTest extends TestCase
{
    public function test_regulatory_mapping_document_records_scope_and_manual_exclusions(): void
    {
        $document = (string) file_get_contents(base_path('docs/11-operacoes/alcanena-regulatory-mapping.md'));

        $this->assertStringContainsString('Artigo 8', $document);
        $this->assertStringContainsString('Artigo 9', $document);
        $this->assertStringContainsString('Artigo 12', $document);
        $this->assertStringContainsString('Artigo 17', $document);
        $this->assertStringContainsString('Out of scope by municipal decision', $document);
        $this->assertStringContainsString('assinatura digital', $document);
        $this->assertStringContainsString('pagamentos via plataforma', $document);
    }
}
