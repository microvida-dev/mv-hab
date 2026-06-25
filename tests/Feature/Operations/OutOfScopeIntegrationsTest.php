<?php

namespace Tests\Feature\Operations;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OutOfScopeIntegrationsTest extends TestCase
{
    public function test_external_integrations_are_formally_out_of_scope_by_municipal_decision(): void
    {
        $document = (string) file_get_contents(base_path('docs/11-operacoes/out-of-scope-integrations.md'));

        foreach ([
            'Assinatura digital',
            'Autenticacao.gov/CMD',
            'MB WAY',
            'Multibanco',
            'Cartao',
            'Gateway de pagamentos',
            'Reconciliacao bancaria automatica',
            'Importacao SEPA automatica',
        ] as $integration) {
            $this->assertStringContainsString($integration, $document);
        }

        $this->assertSame(8, substr_count($document, 'Out of scope by municipal decision'));
        $this->assertStringContainsString('gestao administrativa/manual', $document);
    }

    public function test_no_external_payment_or_government_integration_routes_are_exposed(): void
    {
        $routes = collect(Route::getRoutes())
            ->map(fn ($route): string => strtolower($route->uri().' '.($route->getName() ?? '')))
            ->implode("\n");

        foreach ([
            'mbway',
            'mb-way',
            'multibanco',
            'autenticacao.gov',
            'autenticação.gov',
            'cmd',
            'gateway',
        ] as $forbiddenRouteTerm) {
            $this->assertStringNotContainsString($forbiddenRouteTerm, $routes);
        }

        $this->assertDoesNotMatchRegularExpression('/(^|[\/._-])sepa($|[\/._-])/', $routes);
    }
}
