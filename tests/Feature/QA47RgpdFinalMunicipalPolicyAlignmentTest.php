<?php

namespace Tests\Feature;

use Tests\TestCase;

class QA47RgpdFinalMunicipalPolicyAlignmentTest extends TestCase
{
    public function test_qa47_rgpd_final_policy_alignment_documents_purposes_retention_and_dpo_validation(): void
    {
        $policy = (string) file_get_contents(base_path('docs/11-operacoes/rgpd-final-policy-alignment.md'));
        $retention = (string) file_get_contents(base_path('docs/11-operacoes/data-retention-anonymization-policy.md'));
        $playbook = (string) file_get_contents(base_path('docs/11-operacoes/data-subject-request-playbook.md'));
        $dpo = (string) file_get_contents(base_path('docs/11-operacoes/rgpd-pilot-dpo-validation-checklist.md'));

        foreach (['candidatura', 'documentos', 'elegibilidade', 'scoring', 'listas', 'contratos', 'rendas manuais', 'visitas', 'tickets', 'auditoria'] as $purpose) {
            $this->assertStringContainsString($purpose, strtolower($policy));
        }

        $this->assertStringContainsString('validacao municipal/juridica', $policy);
        $this->assertStringContainsString('retencao', strtolower($retention));
        $this->assertStringContainsString('anonimizacao', strtolower($retention));
        $this->assertStringContainsString('acesso', strtolower($playbook));
        $this->assertStringContainsString('DPO', $dpo);
        $this->assertStringContainsString('IA documental assistiva', $policy);
    }
}
