<?php

namespace App\Services\Search\Sources;

use App\Models\User;
use App\Services\Navigation\WorkspaceService;
use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\SearchResultAuthorizationService;
use App\Services\Search\Sources\Concerns\BuildsSearchResults;

class CommandSearchSource implements SearchSource
{
    use BuildsSearchResults;

    public function __construct(
        private readonly WorkspaceService $workspaces,
        private readonly SearchResultAuthorizationService $authorization,
    ) {}

    public function key(): string
    {
        return 'command';
    }

    public function label(): string
    {
        return 'Comandos';
    }

    public function minimumCharacters(): int
    {
        return 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(User $user, string $term, int $limit): array
    {
        $commands = [
            [
                'label' => 'Abrir Painel Principal',
                'subtitle' => 'Regressar ao Centro de Operações Municipal.',
                'route_name' => 'dashboard',
                'permission' => 'dashboard.view',
                'keywords' => 'dashboard painel principal início centro',
            ],
            [
                'label' => 'Ver minhas tarefas',
                'subtitle' => 'Abrir a caixa de trabalho individual.',
                'route_name' => 'backoffice.work-tasks.my',
                'permission' => 'work_tasks.view',
                'keywords' => 'tarefas minhas trabalho',
            ],
            [
                'label' => 'Abrir produtividade',
                'subtitle' => 'Centro de trabalho, prioridades, caixa de entrada e carga operacional.',
                'route_name' => 'backoffice.productivity.index',
                'permission' => 'work_tasks.view',
                'keywords' => 'produtividade centro trabalho prioridades caixa entrada fila agenda',
            ],
            [
                'label' => 'Ver tarefas vencidas',
                'subtitle' => 'Consultar tarefas com prazo ultrapassado.',
                'route_name' => 'backoffice.work-tasks.overdue',
                'permission' => 'work_tasks.view',
                'keywords' => 'tarefas vencidas prazos sla',
            ],
            [
                'label' => 'Abrir candidaturas',
                'subtitle' => 'Consultar candidaturas autorizadas.',
                'route_name' => 'backoffice.applications.index',
                'permission' => 'applications.view',
                'keywords' => 'candidaturas processos candidatos',
            ],
            [
                'label' => 'Abrir concursos',
                'subtitle' => 'Consultar concursos municipais.',
                'route_name' => 'admin.contests.index',
                'permission' => 'contests.view',
                'keywords' => 'concursos programas avisos',
            ],
            [
                'label' => 'Abrir relatórios',
                'subtitle' => 'Consultar relatórios e KPIs municipais.',
                'route_name' => 'backoffice.reports.index',
                'permission' => 'reports.view',
                'keywords' => 'relatórios kpis indicadores gestão',
            ],
            [
                'label' => 'Abrir auditoria',
                'subtitle' => 'Consultar eventos de auditoria autorizados.',
                'route_name' => 'backoffice.security.audit.events.index',
                'permission' => 'audit_logs.view',
                'keywords' => 'auditoria segurança rgpd eventos',
            ],
        ];

        foreach ($this->workspaces->availableFor($user) as $workspace) {
            $commands[] = [
                'label' => 'Abrir Espaço de Trabalho '.$workspace['title'],
                'subtitle' => (string) $workspace['description'],
                'route_name' => 'workspaces.show',
                'route_parameters' => [(string) $workspace['key']],
                'keywords' => 'workspace espaço trabalho '.(string) $workspace['title'],
            ];
        }

        $results = [];
        foreach ($commands as $command) {
            $routeName = (string) $command['route_name'];
            $permission = isset($command['permission']) ? (string) $command['permission'] : null;
            $searchable = (string) $command['label'].' '.(string) $command['subtitle'].' '.(string) $command['keywords'];

            if (! $this->containsTerm($searchable, $term) || ! $this->authorization->canAccess($user, $routeName, $permission)) {
                continue;
            }

            $results[] = [
                'type' => 'command',
                'group_key' => 'commands',
                'group_label' => $this->label(),
                'label' => (string) $command['label'],
                'subtitle' => (string) $command['subtitle'],
                'route_name' => $routeName,
                'route_parameters' => $command['route_parameters'] ?? [],
                'score' => 95,
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }
}
