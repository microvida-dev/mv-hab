<?php

namespace App\Services\Navigation;

use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\Contest;
use App\Models\ContextualFaq;
use App\Models\Contract;
use App\Models\DataSubjectRequest;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\EligibilityRuleSet;
use App\Models\HousingApplication;
use App\Models\HousingUnit;
use App\Models\HousingVisit;
use App\Models\MaintenanceRequest;
use App\Models\Program;
use App\Models\PropertyInspection;
use App\Models\ReportDefinition;
use App\Models\RequiredDocument;
use App\Models\ScoringRuleSet;
use App\Models\SensitiveDataAccessLog;
use App\Models\SupportTicket;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use App\Models\WorkTask;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class WorkspaceService
{
    /** @var array<int, array<int, string>> */
    private array $roleNamesByUser = [];

    /** @var array<int, array<int, string>> */
    private array $permissionNamesByUser = [];

    /**
     * @return list<array<string, mixed>>
     */
    public function availableFor(User $user): array
    {
        return array_values(array_filter(
            $this->workspaces(),
            fn (array $workspace): bool => $this->workspaceHasVisibleModules($user, $workspace),
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function authorizedWorkspace(User $user, string $key): ?array
    {
        $workspace = $this->find($key);

        if ($workspace === null || ! $this->workspaceHasVisibleModules($user, $workspace)) {
            return null;
        }

        return $workspace;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $key): ?array
    {
        foreach ($this->workspaces() as $workspace) {
            if (($workspace['key'] ?? null) === $key) {
                return $workspace;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function navigationGroups(User $user, ?string $workspaceKey): array
    {
        if ($workspaceKey === null) {
            return $this->workspaceShortcutGroups($user);
        }

        $workspace = $this->authorizedWorkspace($user, $workspaceKey);

        if ($workspace === null) {
            return $this->workspaceShortcutGroups($user);
        }

        return $this->filterGroupsForUser($user, $workspace['groups'] ?? []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function quickActions(User $user): array
    {
        $actions = [
            $this->item('Nova candidatura', 'backoffice.applications.index', 'backoffice.applications.*', 'applications.view'),
            $this->item('Rever documentos', 'admin.document-reviews.index', 'admin.document-reviews.*', 'documents.view'),
            $this->item('Tarefas da minha equipa', 'backoffice.work-tasks.team', 'backoffice.work-tasks.team', 'work_tasks.view_team'),
            $this->item('Relatórios municipais', 'backoffice.reports.index', 'backoffice.reports.*', 'reports.view'),
            $this->item('Segurança e RGPD', 'backoffice.security.dashboard', 'backoffice.security.*', null, ['administrator', 'auditor']),
        ];

        return array_values(array_filter($actions, fn (array $item): bool => $this->canAccessItem($user, $item)));
    }

    /**
     * @return list<array{label: string, examples: list<string>}>
     */
    public function searchGroups(User $user): array
    {
        $groups = [
            ['permission' => 'citizens.view', 'label' => 'Munícipe', 'examples' => ['nome', 'processo']],
            ['permission' => 'contests.view', 'label' => 'Concurso', 'examples' => ['programa', 'estado']],
            ['permission' => 'contracts.view', 'label' => 'Contrato', 'examples' => ['referência', 'inquilino']],
            ['permission' => 'applications.view', 'label' => 'Candidatura', 'examples' => ['número', 'estado']],
            ['permission' => 'documents.view', 'label' => 'Documento', 'examples' => ['tipo', 'estado']],
            ['permission' => 'reports.view', 'label' => 'Relatório', 'examples' => ['KPI', 'exportação']],
            ['permission' => 'housing_units.view', 'label' => 'Fogo', 'examples' => ['tipologia', 'freguesia']],
            ['permission' => 'work_tasks.view', 'label' => 'Tarefa', 'examples' => ['SLA', 'equipa']],
        ];

        return array_values(array_map(
            fn (array $group): array => [
                'label' => (string) $group['label'],
                'examples' => $group['examples'],
            ],
            array_filter($groups, fn (array $group): bool => $this->hasPermission($user, (string) $group['permission'])),
        ));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function canAccessItem(User $user, array $item): bool
    {
        $routeName = $item['route'] ?? null;
        if (is_string($routeName) && ! Route::has($routeName)) {
            return false;
        }

        $roles = $item['roles'] ?? null;
        if (is_array($roles) && ! $this->hasRole($user, array_values(array_filter($roles, 'is_string')))) {
            return false;
        }

        $permission = $item['permission'] ?? null;
        if (is_string($permission) && ! $this->hasPermission($user, $permission)) {
            return false;
        }

        $model = $item['model'] ?? null;
        if (! is_string($permission) && ! is_array($roles) && is_string($model) && class_exists($model) && ! $user->can('viewAny', $model)) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findVisibleItemByRoute(User $user, string $routeName): ?array
    {
        foreach ($this->availableFor($user) as $workspace) {
            foreach ($this->filterGroupsForUser($user, $workspace['groups'] ?? []) as $group) {
                foreach (($group['items'] ?? []) as $item) {
                    if ($this->routeMatchesItem($routeName, $item)) {
                        return $item + ['workspace_key' => $workspace['key'], 'workspace_title' => $workspace['title']];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function workspaces(): array
    {
        return [
            [
                'key' => 'atendimento',
                'icon' => 'contact',
                'title' => 'Atendimento',
                'short_label' => 'AT',
                'description' => 'Munícipes, candidatos, visitas, tickets e FAQ operacional.',
                'accent' => 'teal',
                'groups' => [
                    $this->group('Atendimento', [
                        $this->workspaceDashboard('atendimento'),
                        $this->item('Munícipes', 'citizens.index', 'citizens.*', 'citizens.view'),
                        $this->item('Agregados', 'households.index', 'households.*', 'households.view'),
                        $this->item('Simulador', 'backoffice.simulator.insights.index', 'backoffice.simulator.*', 'simulator.view'),
                        $this->item('Candidaturas', 'backoffice.applications.index', 'backoffice.applications.*', 'applications.view', null, Application::class),
                        $this->item('Receção administrativa', 'backoffice.application-intake.index', 'backoffice.application-intake.*', 'administrative_processes.view'),
                        $this->item('Processos administrativos', 'backoffice.administrative-processes.index', 'backoffice.administrative-processes.*', 'administrative_processes.view'),
                    ]),
                    $this->group('Contacto e suporte', [
                        $this->item('Revisão documental', 'admin.document-reviews.index', 'admin.document-reviews.*', 'documents.view', null, DocumentSubmission::class),
                        $this->item('Visitas abertas', 'backoffice.visit-availabilities.index', 'backoffice.visit-availabilities.*', 'visits.view', null, VisitAvailability::class),
                        $this->item('Horários de visita', 'backoffice.visit-slots.index', 'backoffice.visit-slots.*', 'visits.view', null, VisitSlot::class),
                        $this->item('Visitas agendadas', 'backoffice.housing-visits.index', 'backoffice.housing-visits.*', 'visits.view', null, HousingVisit::class),
                        $this->item('Tickets', 'backoffice.support-tickets.index', 'backoffice.support-tickets.*', 'support.view', null, SupportTicket::class),
                        $this->item('FAQ', 'backoffice.contextual-faqs.index', 'backoffice.contextual-faqs.*', 'contextual_faqs.view', null, ContextualFaq::class),
                    ]),
                ],
            ],
            [
                'key' => 'concursos',
                'icon' => 'contest',
                'title' => 'Concursos',
                'short_label' => 'CO',
                'description' => 'Programas, concursos, elegibilidade, pontuação, listas e publicações.',
                'accent' => 'blue',
                'groups' => [
                    $this->group('Configuração do concurso', [
                        $this->workspaceDashboard('concursos'),
                        $this->item('Programas', 'admin.programs.index', 'admin.programs.*', 'programs.view', null, Program::class),
                        $this->item('Concursos', 'admin.contests.index', 'admin.contests.*', 'contests.view', null, Contest::class),
                        $this->item('Tipos documentais', 'admin.document-types.index', 'admin.document-types.*', 'documents.view', null, DocumentType::class),
                        $this->item('Documentos obrigatórios', 'admin.required-documents.index', 'admin.required-documents.*', 'documents.view', null, RequiredDocument::class),
                    ]),
                    $this->group('Decisão administrativa', [
                        $this->item('Elegibilidade', 'backoffice.eligibility.rule-sets.index', 'backoffice.eligibility.*', 'eligibility.view', null, EligibilityRuleSet::class),
                        $this->item('Pontuação', 'backoffice.scoring.rule-sets.index', 'backoffice.scoring.*', 'scoring.view', null, ScoringRuleSet::class),
                        $this->item('Candidaturas', 'backoffice.applications.index', 'backoffice.applications.*', 'applications.view', null, HousingApplication::class),
                        $this->item('Listas e alocações', 'backoffice.allocation.runs.index', 'backoffice.allocation.*', 'allocations.view'),
                        $this->item('Sorteios', 'backoffice.lottery-draws.index', 'backoffice.lottery-draws.*', 'allocations.view'),
                        $this->item('Publicações', 'backoffice.allocation.reports.index', 'backoffice.allocation.reports.*', 'public_lists.view'),
                    ]),
                ],
            ],
            [
                'key' => 'patrimonio',
                'icon' => 'housing',
                'title' => 'Património',
                'short_label' => 'PA',
                'description' => 'Fogos, empreendimentos, contratos, rendas, manutenção e vistorias.',
                'accent' => 'amber',
                'groups' => [
                    $this->group('Parque habitacional', [
                        $this->workspaceDashboard('patrimonio'),
                        $this->item('Fogos', 'housing-units.index', 'housing-units.*', 'housing_units.view', null, HousingUnit::class),
                        $this->item('Portal público', 'backoffice.public-portal.settings.edit', 'backoffice.public-portal.*', 'settings.view'),
                        $this->item('Links públicos', 'backoffice.public-portal.links.index', 'backoffice.public-portal.links.*', 'settings.view'),
                    ]),
                    $this->group('Pós-atribuição', [
                        $this->item('Contratos', 'backoffice.contracts.leases.index', 'backoffice.contracts.leases.*', 'contracts.view', null, Contract::class),
                        $this->item('Minutas e cláusulas', 'backoffice.contracts.templates.index', 'backoffice.contracts.templates.*', 'contracts.view'),
                        $this->item('Rendas e contas', 'backoffice.finance.accounts.index', 'backoffice.finance.accounts.*', 'finance.view', null, TenantFinancialAccount::class),
                        $this->item('Pagamentos manuais', 'backoffice.finance.payments.index', 'backoffice.finance.payments.*', 'payments.view'),
                        $this->item('Manutenção', 'backoffice.maintenance.index', 'backoffice.maintenance.*', 'maintenance_requests.view', null, MaintenanceRequest::class),
                        $this->item('Vistorias', 'backoffice.inspections.index', 'backoffice.inspections.*', 'inspections.view', null, PropertyInspection::class),
                    ]),
                ],
            ],
            [
                'key' => 'gestao',
                'icon' => 'report',
                'title' => 'Gestão',
                'short_label' => 'GE',
                'description' => 'Relatórios, KPIs, auditoria, RGPD, IA documental e tarefas.',
                'accent' => 'purple',
                'groups' => [
                    $this->group('Operação', [
                        $this->workspaceDashboard('gestao'),
                        $this->item('Produtividade', 'backoffice.productivity.index', 'backoffice.productivity.*', 'work_tasks.view'),
                        $this->item('Tarefas', 'backoffice.work-tasks.my', 'backoffice.work-tasks.*', 'work_tasks.view', null, WorkTask::class),
                        $this->item('Painel de tarefas', 'backoffice.work-tasks.dashboard', 'backoffice.work-tasks.dashboard', 'work_tasks.dashboard'),
                        $this->item('Centro analítico', 'backoffice.analytics.index', 'backoffice.analytics.*', 'reports.view'),
                        $this->item('Relatórios', 'backoffice.reports.index', 'backoffice.reports.*', 'reports.view', null, ReportDefinition::class),
                        $this->item('KPIs operacionais', 'backoffice.reports.operational', 'backoffice.reports.operational', 'reports.view'),
                        $this->item('Painel executivo', 'backoffice.reports.executive', 'backoffice.reports.executive', 'reports.view_executive'),
                    ]),
                    $this->group('Rastreabilidade', [
                        $this->item('Auditoria', 'backoffice.security.audit.events.index', 'backoffice.security.audit.*', 'audit_logs.view', null, AuditEvent::class),
                        $this->item('Acessos sensíveis', 'backoffice.security.audit.access-logs.index', 'backoffice.security.audit.access-logs.*', 'audit_logs.view', null, SensitiveDataAccessLog::class),
                        $this->item('RGPD', 'backoffice.security.privacy.requests.index', 'backoffice.security.privacy.*', 'privacy.view', null, DataSubjectRequest::class),
                        $this->item('IA documental', 'backoffice.document-ai.assistant.index', 'backoffice.document-ai.*', 'documents.view'),
                        $this->item('Comunicações', 'backoffice.communications.index', 'backoffice.communications.*', 'notifications.view'),
                    ]),
                ],
            ],
            [
                'key' => 'administracao',
                'icon' => 'security',
                'title' => 'Administração',
                'short_label' => 'AD',
                'description' => 'Utilizadores, perfis, equipas, permissões, segurança e configuração.',
                'accent' => 'slate',
                'groups' => [
                    $this->group('Acessos e equipas', [
                        $this->workspaceDashboard('administracao'),
                        $this->item('Utilizadores', 'backoffice.users.index', 'backoffice.users.*', 'users.view'),
                        $this->item('Perfis e roles', 'backoffice.roles.index', 'backoffice.roles.*', 'roles.view'),
                        $this->item('Equipas', 'backoffice.teams.index', 'backoffice.teams.*', 'teams.view'),
                        $this->item('Auditoria de acessos', 'backoffice.access-audit.index', 'backoffice.access-audit.*', 'access_audit.view'),
                    ]),
                    $this->group('Segurança operacional', [
                        $this->item('Segurança', 'backoffice.security.dashboard', 'backoffice.security.dashboard', null, ['administrator', 'auditor']),
                        $this->item('MFA', 'backoffice.security.mfa.index', 'backoffice.security.mfa.*', null, ['administrator', 'auditor']),
                        $this->item('Revisão de permissões', 'backoffice.security.permission-reviews.index', 'backoffice.security.permission-reviews.*', null, ['administrator', 'auditor']),
                        $this->item('Retenção RGPD', 'backoffice.security.privacy.retention.index', 'backoffice.security.privacy.retention.*', 'privacy.view'),
                    ]),
                ],
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function group(string $label, array $items): array
    {
        return ['label' => $label, 'items' => $items];
    }

    /**
     * @return array<string, mixed>
     */
    private function workspaceDashboard(string $workspace): array
    {
        return [
            'label' => 'Painel',
            'route' => 'workspaces.show',
            'parameters' => ['workspace' => $workspace],
            'active' => 'workspaces.show',
            'icon' => 'dashboard',
            'workspace_dashboard' => true,
        ];
    }

    /**
     * @param  list<string>|null  $roles
     * @param  class-string|null  $model
     * @return array<string, mixed>
     */
    private function item(string $label, string $route, string $active, ?string $permission = null, ?array $roles = null, ?string $model = null): array
    {
        return array_filter([
            'label' => $label,
            'route' => $route,
            'active' => $active,
            'icon' => $this->iconFor($route, $active, $label),
            'permission' => $permission,
            'roles' => $roles,
            'model' => $model,
        ], fn (mixed $value): bool => $value !== null);
    }

    private function iconFor(string $route, string $active, string $label): string
    {
        return [
            'citizens.index' => 'users',
            'households.index' => 'household',
            'backoffice.simulator.insights.index' => 'simulator',
            'backoffice.applications.index' => 'application',
            'backoffice.application-intake.index' => 'folder',
            'backoffice.administrative-processes.index' => 'process',
            'admin.document-reviews.index' => 'candidate-document',
            'backoffice.visit-availabilities.index' => 'calendar',
            'backoffice.visit-slots.index' => 'schedule',
            'backoffice.housing-visits.index' => 'inspection',
            'backoffice.support-tickets.index' => 'ticket',
            'backoffice.contextual-faqs.index' => 'faq',

            'admin.programs.index' => 'program',
            'admin.contests.index' => 'contest',
            'admin.document-types.index' => 'document',
            'admin.required-documents.index' => 'candidate-document',
            'backoffice.eligibility.rule-sets.index' => 'check',
            'backoffice.scoring.rule-sets.index' => 'balance',
            'backoffice.allocation.runs.index' => 'allocation',
            'backoffice.lottery-draws.index' => 'contest',
            'backoffice.allocation.reports.index' => 'report',

            'housing-units.index' => 'housing',
            'backoffice.public-portal.settings.edit' => 'settings',
            'backoffice.public-portal.links.index' => 'external',
            'backoffice.contracts.leases.index' => 'contract',
            'backoffice.contracts.templates.index' => 'document',
            'backoffice.finance.accounts.index' => 'payment',
            'backoffice.finance.payments.index' => 'payment',
            'backoffice.maintenance.index' => 'maintenance',
            'backoffice.inspections.index' => 'inspection',

            'backoffice.agenda.index' => 'calendar',
            'backoffice.productivity.index' => 'check',
            'backoffice.work-tasks.my' => 'check',
            'backoffice.work-tasks.dashboard' => 'dashboard',
            'backoffice.analytics.index' => 'dashboard',
            'backoffice.reports.index' => 'report',
            'backoffice.reports.operational' => 'report',
            'backoffice.reports.executive' => 'report',
            'backoffice.security.audit.events.index' => 'audit',
            'backoffice.security.audit.access-logs.index' => 'lock',
            'backoffice.security.privacy.requests.index' => 'shield',
            'backoffice.document-ai.assistant.index' => 'document',
            'backoffice.communications.index' => 'communication',

            'backoffice.users.index' => 'users',
            'backoffice.roles.index' => 'security',
            'backoffice.teams.index' => 'users',
            'backoffice.access-audit.index' => 'audit',
            'backoffice.security.dashboard' => 'security',
            'backoffice.security.mfa.index' => 'lock',
            'backoffice.security.permission-reviews.index' => 'shield',
            'backoffice.security.privacy.retention.index' => 'shield',
        ][$route] ?? 'dashboard';
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function workspaceHasVisibleModules(User $user, array $workspace): bool
    {
        foreach (($workspace['groups'] ?? []) as $group) {
            foreach (($group['items'] ?? []) as $item) {
                if (($item['workspace_dashboard'] ?? false) === true) {
                    continue;
                }

                if (is_array($item) && $this->canAccessItem($user, $item)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @return list<array<string, mixed>>
     */
    private function filterGroupsForUser(User $user, array $groups): array
    {
        $filtered = [];

        foreach ($groups as $group) {
            $items = array_values(array_filter(
                $group['items'] ?? [],
                fn (array $item): bool => (($item['workspace_dashboard'] ?? false) === true) || $this->canAccessItem($user, $item),
            ));

            if ($items !== []) {
                $filtered[] = [
                    'label' => $group['label'] ?? 'Navegação',
                    'items' => $items,
                ];
            }
        }

        return $filtered;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function workspaceShortcutGroups(User $user): array
    {
        return [
            $this->group('Operação Municipal', [
                $this->item('Agenda Municipal', 'backoffice.agenda.index', 'backoffice.agenda.*', 'work_tasks.view'),
                $this->item('Caixa de trabalho', 'backoffice.work-tasks.my', 'backoffice.work-tasks.*', 'work_tasks.view', null, WorkTask::class),
                $this->item('Produtividade', 'backoffice.productivity.index', 'backoffice.productivity.*', 'work_tasks.view'),
            ]),
            $this->group('Espaços de Trabalho', array_map(
                fn (array $workspace): array => [
                    'label' => (string) $workspace['title'],
                    'route' => 'workspaces.show',
                    'parameters' => ['workspace' => $workspace['key']],
                    'active' => 'workspaces.show',
                    'icon' => (string) ($workspace['icon'] ?? 'dashboard'),
                ],
                $this->availableFor($user),
            )),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function routeMatchesItem(string $routeName, array $item): bool
    {
        $route = $item['route'] ?? null;
        $active = $item['active'] ?? null;

        return ($route === $routeName)
            || (is_string($active) && Str::is($active, $routeName));
    }

    /**
     * @param  list<string>  $roles
     */
    private function hasRole(User $user, array $roles): bool
    {
        return array_intersect($roles, $this->roleNames($user)) !== [];
    }

    private function hasPermission(User $user, string $permission): bool
    {
        [$module, $action] = str_contains($permission, '.')
            ? explode('.', $permission, 2)
            : [$permission, null];

        foreach ($this->permissionNames($user) as $permissionName) {
            if ($permissionName === '*'
                || $permissionName === $permission
                || $permissionName === $module.'.*'
                || ($action !== null && $permissionName === '*.'.$action)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, string> */
    private function roleNames(User $user): array
    {
        if (! array_key_exists((int) $user->id, $this->roleNamesByUser)) {
            $user->loadMissing('roles.permissions');
            $this->roleNamesByUser[(int) $user->id] = $user->roles
                ->pluck('name')
                ->filter(fn (mixed $name): bool => is_string($name))
                ->values()
                ->all();
        }

        return $this->roleNamesByUser[(int) $user->id];
    }

    /** @return array<int, string> */
    private function permissionNames(User $user): array
    {
        if (! array_key_exists((int) $user->id, $this->permissionNamesByUser)) {
            $user->loadMissing('roles.permissions');
            $this->permissionNamesByUser[(int) $user->id] = $user->roles
                ->flatMap(fn ($role) => $role->permissions->pluck('name'))
                ->filter(fn (mixed $name): bool => is_string($name))
                ->unique()
                ->values()
                ->all();
        }

        return $this->permissionNamesByUser[(int) $user->id];
    }
}
