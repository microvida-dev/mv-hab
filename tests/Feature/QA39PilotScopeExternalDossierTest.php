<?php

namespace Tests\Feature;

use Tests\TestCase;

class QA39PilotScopeExternalDossierTest extends TestCase
{
    public function test_pilot_scope_documents_external_integrations_as_out_of_scope(): void
    {
        $scope = (string) file_get_contents(base_path('docs/11-operacoes/pilot-scope-alcanena.md'));
        $dossier = (string) file_get_contents(base_path('docs/11-operacoes/external-dossier-sanitization-checklist.md'));

        foreach ([
            'CMD',
            'Autenticacao.gov',
            'pagamentos via plataforma',
            'MB WAY',
            'Multibanco',
            'cartao',
            'gateway de pagamentos',
            'reconciliacao bancaria automatica',
            'importacao SEPA automatica',
            'assinatura digital qualificada',
        ] as $term) {
            $this->assertStringContainsString($term, $scope);
        }

        $this->assertStringContainsString('Out of scope by municipal decision', $scope);
        $this->assertStringContainsString('dados ficticios', $scope);
        $this->assertStringContainsString('restore/rollback real', $scope);
        $this->assertStringContainsString('sem promessas funcionais indevidas', $dossier);
    }

    public function test_external_dossier_docs_do_not_claim_excluded_integrations_are_active(): void
    {
        $documents = [
            'docs/11-operacoes/out-of-scope-integrations.md',
            'docs/11-operacoes/pilot-scope-alcanena.md',
            'docs/11-operacoes/external-dossier-sanitization-checklist.md',
        ];

        foreach ($documents as $document) {
            $contents = strtolower((string) file_get_contents(base_path($document)));

            foreach ([
                'cmd ativo',
                'autenticacao.gov ativo',
                'assinatura digital ativa',
                'mb way ativo',
                'multibanco ativo',
                'gateway ativo',
                'reconciliacao bancaria automatica ativa',
            ] as $forbiddenClaim) {
                $this->assertStringNotContainsString($forbiddenClaim, $contents);
            }
        }
    }
}
