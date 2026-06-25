<?php

namespace Tests\Unit\Rgpd;

use Tests\TestCase;

class RgpdPolicyMappingTest extends TestCase
{
    public function test_rgpd_policy_mapping_keeps_external_integrations_out_of_scope(): void
    {
        $policy = (string) file_get_contents(base_path('docs/11-operacoes/rgpd-final-policy-alignment.md'));
        $scope = (string) file_get_contents(base_path('docs/11-operacoes/out-of-scope-integrations.md'));

        $this->assertStringContainsString('decisao humana', strtolower($policy));
        $this->assertStringContainsString('Out of scope by municipal decision', $scope);
        $this->assertStringNotContainsString('CMD ativo', $scope);
        $this->assertStringNotContainsString('pagamentos digitais ativos', strtolower($scope));
    }
}
