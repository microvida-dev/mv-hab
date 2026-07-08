<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Services\Navigation\FavoritesService;
use App\Services\Navigation\RecentItemsService;
use App\Services\Navigation\WorkspaceService;
use App\Services\Navigation\WorkspacePreferenceService;
use Illuminate\Support\Facades\Route;

class ProfileDashboardService
{
    public function __construct(
        private readonly DashboardAuthorizationService $authorization,
        private readonly DashboardWidgetRegistry $widgets,
        private readonly DashboardMetricService $metrics,
        private readonly DashboardQuickActionService $quickActions,
        private readonly DashboardDeadlineService $deadlines,
        private readonly WorkspaceService $workspaces,
        private readonly FavoritesService $favorites,
        private readonly RecentItemsService $recentItems,
        private readonly WorkspacePreferenceService $workspacePreferences,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $user->loadMissing(['roles', 'municipalTeams']);

        $workspaces = $this->workspaces->availableFor($user);
        $favorites = $this->favorites->forUser($user);
        $recentItems = $this->recentItems->forUser($user);
        $workspaceIntelligence = $this->workspaceIntelligence($user);
        $widgets = $this->widgets->forUser($user);
        $metrics = $this->metrics->forUser($user);
        $quickActions = $this->quickActions->forUser($user);
        $deadlines = $this->deadlines->forUser($user);
        $adaptiveDashboard = $this->adaptiveDashboard($user, $metrics, $widgets, $quickActions, $deadlines, $workspaceIntelligence);

        return [
            'greeting' => $this->greeting($user),
            'profile_label' => $this->authorization->profileLabel($user),
            'profile_keys' => $this->authorization->profileKeys($user),
            'team_names' => $this->teamNames($user),
            'workspaces' => $workspaces,
            'favorites' => $favorites,
            'recent_items' => $recentItems,
            'workspace_intelligence' => $workspaceIntelligence,
            'adaptive_dashboard' => $adaptiveDashboard,
            'priority_queue' => $this->priorityQueue($adaptiveDashboard, $deadlines, $widgets, $metrics, $quickActions),
            'search_groups' => $this->workspaces->searchGroups($user),
            'widgets' => $widgets,
            'metrics' => $metrics,
            'quick_actions' => $quickActions,
            'deadlines' => $deadlines,
            'notifications_summary' => $this->notificationsSummary(),
            'workspace_preferences' => $this->workspacePreferences->payloadFor($user),
        ];
    }

    private function greeting(User $user): string
    {
        $firstName = trim(explode(' ', trim($user->name))[0]);
        $name = $firstName !== '' ? $firstName : 'utilizador';

        return 'Bom trabalho, '.$name;
    }

    /**
     * @return array<int, string>
     */
    private function teamNames(User $user): array
    {
        return $user->municipalTeams()
            ->wherePivotNull('left_at')
            ->orderBy('municipal_teams.name')
            ->pluck('municipal_teams.name')
            ->filter(fn (mixed $name): bool => is_string($name))
            ->values()
            ->all();
    }

    /**
     * @return array{label: string, description: string}
     */
    private function notificationsSummary(): array
    {
        return [
            'label' => 'Notificações operacionais',
            'description' => 'As notificações continuam ligadas aos módulos existentes e são apresentadas aqui como ponto global de atenção.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function workspaceIntelligence(User $user): array
    {
        $workspaces = collect($this->workspaces->availableFor($user));
        $favorites = collect($this->favorites->forUser($user));
        $recentItems = collect($this->recentItems->forUser($user));
        $preferences = $this->workspacePreferences->payloadFor($user);

        $preferredKey = $preferences['preferred_workspace'] ?? null;

        $preferred = is_string($preferredKey)
            ? $workspaces->firstWhere('key', $preferredKey)
            : null;

        $preferred ??= $workspaces->first();

        return [
            'preferred_key' => $preferred['key'] ?? null,
            'preferred' => $preferred ? $this->workspaceCardPayload($preferred, $favorites, $recentItems, true) : null,
            'workspaces' => $workspaces
                ->map(fn (array $workspace): array => $this->workspaceCardPayload(
                    $workspace,
                    $favorites,
                    $recentItems,
                    ($preferred['key'] ?? null) === ($workspace['key'] ?? null),
                ))
                ->values()
                ->all(),
            'summary' => [
                'workspaces' => $workspaces->count(),
                'favorites' => $favorites->count(),
                'recent_items' => $recentItems->count(),
                'preferred_label' => $preferred['title'] ?? null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $workspace
     * @param  \Illuminate\Support\Collection<int, mixed>  $favorites
     * @param  \Illuminate\Support\Collection<int, mixed>  $recentItems
     * @return array<string, mixed>
     */
    private function workspaceCardPayload(array $workspace, $favorites, $recentItems, bool $preferred): array
    {
        $key = (string) ($workspace['key'] ?? '');

        $workspaceFavorites = $favorites->filter(
            fn (mixed $favorite): bool => data_get($favorite, 'workspace_key') === $key
        );

        $workspaceRecentItems = $recentItems->filter(
            fn (mixed $item): bool => data_get($item, 'workspace_key') === $key
        );

        return [
            'key' => $key,
            'title' => (string) ($workspace['title'] ?? 'Workspace'),
            'description' => (string) ($workspace['description'] ?? ''),
            'icon' => (string) ($workspace['icon'] ?? 'dashboard'),
            'href' => route('workspaces.show', $key),
            'is_preferred' => $preferred,
            'favorites_count' => $workspaceFavorites->count(),
            'recent_count' => $workspaceRecentItems->count(),
            'modules_count' => collect($workspace['groups'] ?? [])
                ->flatMap(fn (array $group): array => $group['items'] ?? [])
                ->count(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $metrics
     * @param  array<int, array<string, mixed>>  $widgets
     * @param  array<int, array<string, mixed>>  $quickActions
     * @param  array<int, array<string, mixed>>  $deadlines
     * @param  array<string, mixed>  $workspaceIntelligence
     * @return array<string, mixed>
     */
    private function adaptiveDashboard(
        User $user,
        array $metrics,
        array $widgets,
        array $quickActions,
        array $deadlines,
        array $workspaceIntelligence,
    ): array {
        $profile = $this->authorization->primaryProfile($user);
        $context = $this->adaptiveContext($profile);
        $riskLevel = $this->riskLevel($deadlines);

        return [
            'profile' => $profile,
            'profile_label' => $this->authorization->profileLabel($user),
            'eyebrow' => $context['eyebrow'],
            'headline' => $context['headline'],
            'description' => $context['description'],
            'icon' => $context['icon'],
            'tone' => $context['tone'],
            'risk_level' => $riskLevel,
            'risk_label' => $this->riskLabel($riskLevel),
            'primary_workspace_label' => data_get($workspaceIntelligence, 'summary.preferred_label'),
            'primary_action' => $this->primaryActionPayload($quickActions, $context['action_labels']),
            'focus_metrics' => $this->pickByKeys($metrics, $context['metric_keys']),
            'priority_widgets' => $this->pickByKeys($widgets, $context['widget_keys']),
            'summary' => [
                'active_deadlines' => collect($deadlines)
                    ->filter(fn (array $deadline): bool => (int) data_get($deadline, 'count', 0) > 0)
                    ->count(),
                'available_actions' => count($quickActions),
                'available_widgets' => count($widgets),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adaptiveContext(string $profile): array
    {
        return match ($profile) {
            'administrator' => [
                'eyebrow' => 'Administração municipal',
                'headline' => 'Visão global da operação',
                'description' => 'Acompanhe segurança, equipas, riscos e produtividade transversal da plataforma.',
                'icon' => 'security',
                'tone' => 'info',
                'metric_keys' => ['security_alerts', 'active_users', 'active_teams', 'overdue_tasks'],
                'widget_keys' => ['admin_security', 'admin_operations'],
                'action_labels' => ['Rever segurança', 'Tarefas', 'Relatórios municipais'],
            ],
            'municipal_technician' => [
                'eyebrow' => 'Operação técnica',
                'headline' => 'Foco técnico',
                'description' => 'Documentos, candidaturas, aperfeiçoamentos e SLA operacional.',
                'icon' => 'document',
                'tone' => 'warning',
                'metric_keys' => ['pending_documents', 'pending_applications', 'assigned_tasks', 'overdue_tasks'],
                'widget_keys' => ['technical_review'],
                'action_labels' => ['Rever documentos', 'Abrir candidaturas', 'Ver tarefas'],
            ],
            'jury' => [
                'eyebrow' => 'Júri e decisão',
                'headline' => 'Classificação e listas',
                'description' => 'Priorize pontuação, reclamações, listas provisórias e decisões do procedimento.',
                'icon' => 'contest',
                'tone' => 'primary',
                'metric_keys' => ['pending_applications', 'pending_complaints', 'overdue_tasks'],
                'widget_keys' => ['jury_decision'],
                'action_labels' => ['Classificar processos', 'Ver listas', 'Ver reclamações'],
            ],
            'legal_manager' => [
                'eyebrow' => 'Validação jurídica',
                'headline' => 'Contratos e audiência prévia',
                'description' => 'Acompanhe contratos, reclamações, pareceres e prazos jurídicos.',
                'icon' => 'contract',
                'tone' => 'info',
                'metric_keys' => ['pending_contracts', 'pending_complaints', 'overdue_tasks'],
                'widget_keys' => ['legal_review'],
                'action_labels' => ['Rever contratos', 'Reclamações jurídicas', 'Audiência prévia'],
            ],
            'financial_manager' => [
                'eyebrow' => 'Gestão financeira',
                'headline' => 'Pagamentos e rendas',
                'description' => 'Controle pagamentos, rendas manuais e contratos com impacto financeiro.',
                'icon' => 'payment',
                'tone' => 'info',
                'metric_keys' => ['pending_payments', 'pending_rents', 'pending_contracts'],
                'widget_keys' => ['financial_control'],
                'action_labels' => ['Ver pagamentos', 'Ver rendas', 'Ver contratos'],
            ],
            'housing_manager' => [
                'eyebrow' => 'Gestão habitacional',
                'headline' => 'Fogos, ocupação e visitas',
                'description' => 'Acompanhe disponibilidade, contratos operacionais e visitas aos fogos.',
                'icon' => 'housing',
                'tone' => 'primary',
                'metric_keys' => ['available_housing', 'upcoming_visits', 'pending_contracts'],
                'widget_keys' => ['housing_operations'],
                'action_labels' => ['Ver fogos', 'Criar visitas abertas', 'Ver contratos'],
            ],
            'maintenance_manager' => [
                'eyebrow' => 'Manutenção',
                'headline' => 'Pedidos urgentes e intervenções',
                'description' => 'Priorize pedidos críticos, vistorias e tarefas vencidas.',
                'icon' => 'maintenance',
                'tone' => 'warning',
                'metric_keys' => ['urgent_maintenance', 'scheduled_inspections', 'overdue_tasks'],
                'widget_keys' => ['maintenance_operations'],
                'action_labels' => ['Pedidos urgentes', 'Ver vistorias', 'Tarefas vencidas'],
            ],
            'inspection_manager' => [
                'eyebrow' => 'Vistorias',
                'headline' => 'Agenda técnica e autos',
                'description' => 'Acompanhe vistorias agendadas, histórico técnico e tarefas de inspeção.',
                'icon' => 'inspection',
                'tone' => 'info',
                'metric_keys' => ['scheduled_inspections', 'urgent_maintenance', 'assigned_tasks'],
                'widget_keys' => ['inspection_operations'],
                'action_labels' => ['Vistorias agendadas', 'Tarefas de vistoria', 'Histórico de imóveis'],
            ],
            'support_agent' => [
                'eyebrow' => 'Atendimento',
                'headline' => 'Apoio ao candidato',
                'description' => 'Centralize tickets, visitas, contactos pendentes e FAQ operacional.',
                'icon' => 'support',
                'tone' => 'primary',
                'metric_keys' => ['open_tickets', 'upcoming_visits', 'pending_documents'],
                'widget_keys' => ['candidate_support'],
                'action_labels' => ['Tickets abertos', 'Visitas marcadas', 'FAQ operacional'],
            ],
            'auditor' => [
                'eyebrow' => 'Auditoria',
                'headline' => 'Rastreabilidade e conformidade',
                'description' => 'Consulte eventos, acessos sensíveis, pedidos RGPD e relatórios autorizados.',
                'icon' => 'security',
                'tone' => 'neutral',
                'metric_keys' => ['recent_audit_events', 'rgpd_requests', 'security_alerts'],
                'widget_keys' => ['audit_readonly'],
                'action_labels' => ['Ver auditoria', 'Acessos sensíveis', 'Relatórios'],
            ],
            default => [
                'eyebrow' => 'Backoffice municipal',
                'headline' => 'Painel operacional',
                'description' => 'Acompanhe os módulos disponíveis e continue a operação municipal.',
                'icon' => 'dashboard',
                'tone' => 'neutral',
                'metric_keys' => ['assigned_tasks', 'pending_documents', 'pending_applications'],
                'widget_keys' => [],
                'action_labels' => [],
            ],
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, string>  $keys
     * @return array<int, array<string, mixed>>
     */
    private function pickByKeys(array $items, array $keys, int $limit = 4): array
    {
        $collection = collect($items);

        $picked = collect($keys)
            ->map(fn (string $key): ?array => $collection->firstWhere('key', $key))
            ->filter()
            ->values();

        if ($picked->isEmpty()) {
            return $collection->take($limit)->values()->all();
        }

        return $picked->take($limit)->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $actions
     * @param  array<int, string>  $labels
     * @return array<string, mixed>|null
     */
    private function primaryActionPayload(array $actions, array $labels): ?array
    {
        $collection = collect($actions);

        $action = collect($labels)
            ->map(fn (string $label): ?array => $collection->firstWhere('label', $label))
            ->filter()
            ->first();

        $action ??= $collection->first();

        if (! is_array($action)) {
            return null;
        }

        $route = data_get($action, 'route');
        $parameters = data_get($action, 'parameters', []);

        return [
            'label' => (string) data_get($action, 'label', 'Abrir'),
            'description' => (string) data_get($action, 'description', ''),
            'href' => is_string($route) && Route::has($route)
                ? route($route, is_array($parameters) ? $parameters : [])
                : null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $deadlines
     */
    private function riskLevel(array $deadlines): string
    {
        $active = collect($deadlines)
            ->filter(fn (array $deadline): bool => (int) data_get($deadline, 'count', 0) > 0);

        if ($active->contains(fn (array $deadline): bool => data_get($deadline, 'tone') === 'danger')) {
            return 'danger';
        }

        if ($active->isNotEmpty()) {
            return 'warning';
        }

        return 'success';
    }

    private function riskLabel(string $riskLevel): string
    {
        return match ($riskLevel) {
            'danger' => 'Atenção crítica',
            'warning' => 'Atenção recomendada',
            'success' => 'Operação estável',
            default => 'Sem leitura de risco',
        };
    }

    /**
     * @param  array<string, mixed>  $adaptiveDashboard
     * @param  array<int, array<string, mixed>>  $deadlines
     * @param  array<int, array<string, mixed>>  $widgets
     * @param  array<int, array<string, mixed>>  $metrics
     * @param  array<int, array<string, mixed>>  $quickActions
     * @return array<string, mixed>
     */
    private function priorityQueue(
        array $adaptiveDashboard,
        array $deadlines,
        array $widgets,
        array $metrics,
        array $quickActions,
    ): array {
        $items = collect()
            ->merge($this->deadlinePriorityItems($deadlines))
            ->merge($this->widgetPriorityItems($widgets))
            ->merge($this->metricPriorityItems($metrics))
            ->merge($this->actionPriorityItems($adaptiveDashboard, $quickActions))
            ->unique('key')
            ->sortBy(fn (array $item): string => str_pad((string) data_get($item, 'weight', 99), 3, '0', STR_PAD_LEFT).'|'.data_get($item, 'title', ''))
            ->take(6)
            ->values();

        return [
            'items' => $items->all(),
            'summary' => [
                'count' => $items->count(),
                'critical' => $items->where('priority', 'critical')->count(),
                'high' => $items->where('priority', 'high')->count(),
                'label' => $items->isEmpty()
                    ? 'Sem prioridades ativas'
                    : $items->count().' prioridade(s)',
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $deadlines
     * @return array<int, array<string, mixed>>
     */
    private function deadlinePriorityItems(array $deadlines): array
    {
        return collect($deadlines)
            ->filter(fn (array $deadline): bool => (int) data_get($deadline, 'count', 0) > 0)
            ->map(fn (array $deadline): array => [
                'key' => 'deadline_'.data_get($deadline, 'key', md5(json_encode($deadline) ?: 'deadline')),
                'source' => 'Prazo',
                'title' => (string) data_get($deadline, 'label', 'Prazo operacional'),
                'description' => (string) data_get($deadline, 'description', ''),
                'count' => (int) data_get($deadline, 'count', 0),
                'icon' => 'calendar',
                'tone' => (string) data_get($deadline, 'tone', 'warning'),
                'priority' => $this->priorityFromTone((string) data_get($deadline, 'tone', 'warning')),
                'href' => $this->routeHref(data_get($deadline, 'route')),
                'cta' => 'Abrir alerta',
                'weight' => $this->weightFromTone((string) data_get($deadline, 'tone', 'warning')),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $widgets
     * @return array<int, array<string, mixed>>
     */
    private function widgetPriorityItems(array $widgets): array
    {
        return collect($widgets)
            ->filter(fn (array $widget): bool => in_array(data_get($widget, 'priority'), ['critical', 'high', 'medium'], true))
            ->map(fn (array $widget): array => [
                'key' => 'widget_'.data_get($widget, 'key', md5(json_encode($widget) ?: 'widget')),
                'source' => 'Widget',
                'title' => (string) data_get($widget, 'title', 'Widget operacional'),
                'description' => (string) data_get($widget, 'description', ''),
                'count' => data_get($widget, 'value'),
                'icon' => (string) data_get($widget, 'icon', 'dashboard'),
                'tone' => (string) data_get($widget, 'tone', 'neutral'),
                'priority' => (string) data_get($widget, 'priority', 'medium'),
                'href' => data_get($widget, 'href'),
                'cta' => (string) data_get($widget, 'cta', 'Abrir'),
                'weight' => $this->weightFromPriority((string) data_get($widget, 'priority', 'medium')),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $metrics
     * @return array<int, array<string, mixed>>
     */
    private function metricPriorityItems(array $metrics): array
    {
        return collect($metrics)
            ->filter(fn (array $metric): bool => (int) data_get($metric, 'value', 0) > 0)
            ->filter(fn (array $metric): bool => in_array(data_get($metric, 'tone'), ['danger', 'warning'], true))
            ->map(fn (array $metric): array => [
                'key' => 'metric_'.data_get($metric, 'key', md5(json_encode($metric) ?: 'metric')),
                'source' => 'Indicador',
                'title' => (string) data_get($metric, 'label', 'Indicador operacional'),
                'description' => (string) data_get($metric, 'description', ''),
                'count' => (int) data_get($metric, 'value', 0),
                'icon' => 'dashboard',
                'tone' => (string) data_get($metric, 'tone', 'warning'),
                'priority' => $this->priorityFromTone((string) data_get($metric, 'tone', 'warning')),
                'href' => $this->routeHref(data_get($metric, 'route')),
                'cta' => 'Abrir indicador',
                'weight' => $this->weightFromTone((string) data_get($metric, 'tone', 'warning')) + 5,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $adaptiveDashboard
     * @param  array<int, array<string, mixed>>  $quickActions
     * @return array<int, array<string, mixed>>
     */
    private function actionPriorityItems(array $adaptiveDashboard, array $quickActions): array
    {
        $primaryAction = data_get($adaptiveDashboard, 'primary_action');

        if (! is_array($primaryAction)) {
            $primaryAction = collect($quickActions)->first();
        }

        if (! is_array($primaryAction)) {
            return [];
        }

        $route = data_get($primaryAction, 'route');
        $parameters = data_get($primaryAction, 'parameters', []);

        return [[
            'key' => 'action_primary',
            'source' => 'Ação',
            'title' => (string) data_get($primaryAction, 'label', 'Ação principal'),
            'description' => (string) data_get($primaryAction, 'description', ''),
            'count' => null,
            'icon' => 'arrow-right',
            'tone' => 'primary',
            'priority' => 'medium',
            'href' => data_get($primaryAction, 'href') ?: $this->routeHref($route, is_array($parameters) ? $parameters : []),
            'cta' => 'Abrir prioridade',
            'weight' => 70,
        ]];
    }

    /**
     * @param  mixed  $route
     * @param  array<string, mixed>  $parameters
     */
    private function routeHref(mixed $route, array $parameters = []): ?string
    {
        return is_string($route) && Route::has($route)
            ? route($route, $parameters)
            : null;
    }

    private function priorityFromTone(string $tone): string
    {
        return match ($tone) {
            'danger' => 'critical',
            'warning' => 'high',
            'primary', 'info', 'civic' => 'medium',
            default => 'low',
        };
    }

    private function weightFromTone(string $tone): int
    {
        return $this->weightFromPriority($this->priorityFromTone($tone));
    }

    private function weightFromPriority(string $priority): int
    {
        return match ($priority) {
            'critical' => 10,
            'high' => 20,
            'medium' => 40,
            default => 70,
        };
    }
}
