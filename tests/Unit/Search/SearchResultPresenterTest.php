<?php

namespace Tests\Unit\Search;

use App\Services\Search\SearchResultPresenter;
use Tests\TestCase;

class SearchResultPresenterTest extends TestCase
{
    public function test_presenter_groups_valid_results_and_omits_invalid_routes(): void
    {
        $presenter = new SearchResultPresenter;

        $groups = $presenter->grouped([
            [
                'type' => 'command',
                'group_key' => 'commands',
                'group_label' => 'Comandos',
                'label' => 'Abrir Painel Principal',
                'subtitle' => 'Dashboard municipal',
                'route_name' => 'dashboard',
                'route_parameters' => [],
                'score' => 100,
            ],
            [
                'type' => 'broken',
                'group_key' => 'broken',
                'group_label' => 'Inválido',
                'label' => 'Rota inexistente',
                'route_name' => 'missing.route',
                'route_parameters' => [],
                'score' => 100,
            ],
        ]);

        $this->assertCount(1, $groups);
        $this->assertSame('Comandos', $groups[0]['label']);
        $this->assertSame('Abrir Painel Principal', $groups[0]['results'][0]['label']);
    }
}
