@php
    $user = Auth::user();
    $isCandidate = $user->hasRole('candidate');
    $candidateNavigation = $isCandidate
        ? app(\App\Services\CandidateExperience\CandidateNavigationService::class)->forUser($user)
        : null;
    $homeRoute = $candidateNavigation['home_route'] ?? 'dashboard';
    $navigationFooterLinks = $candidateNavigation['footer'] ?? [];

    $navigationGroups = $isCandidate ? $candidateNavigation['groups'] : [
        'Operação' => [
            ['label' => 'Painel Principal', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'dashboard'],
            ['label' => 'Caixa de trabalho', 'route' => 'backoffice.work-tasks.my', 'active' => 'backoffice.work-tasks.*', 'icon' => 'check', 'model' => \App\Models\WorkTask::class],
            ['label' => 'Exploração pós-atribuição', 'route' => 'backoffice.tenant-operations.dashboard', 'active' => 'backoffice.tenant-operations.*', 'icon' => 'home', 'model' => \App\Models\LandlordDashboardSnapshot::class],
        ],
        'Programas' => [
            ['label' => 'Programas', 'route' => 'admin.programs.index', 'active' => 'admin.programs.*', 'icon' => 'document', 'model' => \App\Models\Program::class],
            ['label' => 'Concursos', 'route' => 'admin.contests.index', 'active' => 'admin.contests.*', 'icon' => 'file', 'model' => \App\Models\Contest::class],
            ['label' => 'Tipos documentais', 'route' => 'admin.document-types.index', 'active' => 'admin.document-types.*', 'icon' => 'document', 'model' => \App\Models\DocumentType::class],
            ['label' => 'Regras documentais', 'route' => 'admin.required-documents.index', 'active' => 'admin.required-documents.*', 'icon' => 'file', 'model' => \App\Models\RequiredDocument::class],
            ['label' => 'Regras de elegibilidade', 'route' => 'backoffice.eligibility.rule-sets.index', 'active' => 'backoffice.eligibility.rule-sets.*', 'icon' => 'check', 'model' => \App\Models\EligibilityRuleSet::class],
            ['label' => 'Classificação', 'route' => 'backoffice.scoring.rule-sets.index', 'active' => 'backoffice.scoring.*', 'icon' => 'check', 'model' => \App\Models\ScoringRuleSet::class],
        ],
        'Atendimento' => [
            ['label' => 'Munícipes', 'route' => 'citizens.index', 'active' => 'citizens.*', 'icon' => 'users'],
            ['label' => 'Agregados', 'route' => 'households.index', 'active' => 'households.*', 'icon' => 'users'],
            ['label' => 'Candidaturas formais', 'route' => 'backoffice.applications.index', 'active' => 'backoffice.applications.*', 'icon' => 'file', 'model' => \App\Models\Application::class],
            ['label' => 'Indicadores do simulador', 'route' => 'backoffice.simulator.insights.index', 'active' => 'backoffice.simulator.*', 'icon' => 'dashboard', 'permission' => 'simulator.view'],
            ['label' => 'Receção administrativa', 'route' => 'backoffice.application-intake.index', 'active' => 'backoffice.application-intake.*', 'icon' => 'file', 'model' => \App\Models\AdministrativeProcess::class],
            ['label' => 'Processos administrativos', 'route' => 'backoffice.administrative-processes.index', 'active' => 'backoffice.administrative-processes.*', 'icon' => 'document', 'model' => \App\Models\AdministrativeProcess::class],
            ['label' => 'Tarefas administrativas', 'route' => 'backoffice.administrative-tasks.index', 'active' => 'backoffice.administrative-tasks.*', 'icon' => 'check', 'model' => \App\Models\AdministrativeTask::class],
            ['label' => 'Candidaturas CRM', 'route' => 'applications.index', 'active' => 'applications.*', 'icon' => 'file'],
            ['label' => 'Revisão documental', 'route' => 'admin.document-reviews.index', 'active' => 'admin.document-reviews.*', 'icon' => 'document', 'model' => \App\Models\DocumentSubmission::class],
            ['label' => 'Classificação IA', 'route' => 'backoffice.document-ai.classifications.index', 'active' => 'backoffice.document-ai.classifications.*', 'icon' => 'check', 'model' => \App\Models\DocumentAiAnalysis::class],
            ['label' => 'Extração IA', 'route' => 'backoffice.document-ai.extractions.index', 'active' => 'backoffice.document-ai.extractions.*', 'icon' => 'document', 'model' => \App\Models\DocumentAiAnalysis::class],
            ['label' => 'Validação IA', 'route' => 'backoffice.document-ai.validations.index', 'active' => 'backoffice.document-ai.validations.*', 'icon' => 'alert', 'model' => \App\Models\DocumentAiValidationRun::class],
            ['label' => 'Assistente IA', 'route' => 'backoffice.document-ai.assistant.index', 'active' => 'backoffice.document-ai.assistant.*', 'icon' => 'alert', 'model' => \App\Models\DocumentAiScore::class],
            ['label' => 'Verificações de elegibilidade', 'route' => 'backoffice.eligibility.checks.index', 'active' => 'backoffice.eligibility.checks.*', 'icon' => 'check', 'model' => \App\Models\EligibilityCheck::class],
            ['label' => 'Documentos', 'route' => 'documents.index', 'active' => 'documents.*', 'icon' => 'document'],
            ['label' => 'Visitas abertas', 'route' => 'backoffice.visit-availabilities.index', 'active' => 'backoffice.visit-availabilities.*', 'icon' => 'home', 'model' => \App\Models\VisitAvailability::class],
            ['label' => 'Horários de visita', 'route' => 'backoffice.visit-slots.index', 'active' => 'backoffice.visit-slots.*', 'icon' => 'check', 'model' => \App\Models\VisitSlot::class],
            ['label' => 'Visitas agendadas', 'route' => 'backoffice.housing-visits.index', 'active' => 'backoffice.housing-visits.*', 'icon' => 'home', 'model' => \App\Models\HousingVisit::class],
            ['label' => 'Tickets de apoio', 'route' => 'backoffice.support-tickets.index', 'active' => 'backoffice.support-tickets.*', 'icon' => 'alert', 'model' => \App\Models\SupportTicket::class],
            ['label' => 'FAQ contextual', 'route' => 'backoffice.contextual-faqs.index', 'active' => 'backoffice.contextual-faqs.*', 'icon' => 'check', 'model' => \App\Models\ContextualFaq::class],
            ['label' => 'Inconsistências', 'route' => 'backoffice.application-inconsistencies.index', 'active' => 'backoffice.application-inconsistencies.*', 'icon' => 'alert', 'model' => \App\Models\ApplicationSimulationInconsistency::class],
        ],
        'Atribuição' => [
            ['label' => 'Execuções', 'route' => 'backoffice.allocation.runs.index', 'active' => 'backoffice.allocation.runs.*', 'icon' => 'check', 'model' => \App\Models\AllocationRun::class],
            ['label' => 'Habitações por concurso', 'route' => 'backoffice.allocation.contest-housing-units.index', 'active' => 'backoffice.allocation.contest-housing-units.*', 'icon' => 'home', 'model' => \App\Models\ContestHousingUnit::class],
            ['label' => 'Regras de adequação', 'route' => 'backoffice.allocation.typology-rules.index', 'active' => 'backoffice.allocation.typology-rules.*', 'icon' => 'document', 'model' => \App\Models\TypologyAdequacyRule::class],
            ['label' => 'Regras de atribuição', 'route' => 'backoffice.allocation.rule-sets.index', 'active' => 'backoffice.allocation.rule-sets.*', 'icon' => 'document', 'model' => \App\Models\AllocationRuleSet::class],
            ['label' => 'Atribuições', 'route' => 'backoffice.allocation.allocations.index', 'active' => 'backoffice.allocation.allocations.*', 'icon' => 'file', 'model' => \App\Models\Allocation::class],
            ['label' => 'Ofertas', 'route' => 'backoffice.allocation.offers.index', 'active' => 'backoffice.allocation.offers.*', 'icon' => 'file', 'model' => \App\Models\AllocationOffer::class],
            ['label' => 'Sorteios', 'route' => 'backoffice.allocation.lotteries.index', 'active' => 'backoffice.allocation.lotteries.*', 'icon' => 'check', 'model' => \App\Models\LotteryRun::class],
            ['label' => 'Listas suplentes', 'route' => 'backoffice.allocation.reserve-lists.index', 'active' => 'backoffice.allocation.reserve-lists.*', 'icon' => 'users', 'model' => \App\Models\ReserveList::class],
            ['label' => 'Relatórios', 'route' => 'backoffice.allocation.reports.index', 'active' => 'backoffice.allocation.reports.*', 'icon' => 'document', 'model' => \App\Models\AllocationReport::class],
        ],
        'Contratação' => [
            ['label' => 'Regras de renda', 'route' => 'backoffice.contracts.rent-rule-sets.index', 'active' => 'backoffice.contracts.rent-rule-sets.*', 'icon' => 'wallet', 'model' => \App\Models\RentRuleSet::class],
            ['label' => 'Cálculos de renda', 'route' => 'backoffice.contracts.rent-calculations.index', 'active' => 'backoffice.contracts.rent-calculations.*', 'icon' => 'wallet', 'model' => \App\Models\RentCalculation::class],
            ['label' => 'Minutas', 'route' => 'backoffice.contracts.templates.index', 'active' => 'backoffice.contracts.templates.*', 'icon' => 'document', 'model' => \App\Models\ContractTemplate::class],
            ['label' => 'Cláusulas', 'route' => 'backoffice.contracts.clauses.index', 'active' => 'backoffice.contracts.clauses.*', 'icon' => 'file', 'model' => \App\Models\ContractClause::class],
            ['label' => 'Contratos processuais', 'route' => 'backoffice.contracts.leases.index', 'active' => 'backoffice.contracts.leases.*', 'icon' => 'document', 'model' => \App\Models\Contract::class],
        ],
        'Financeiro' => [
            ['label' => 'Contas financeiras', 'route' => 'backoffice.finance.accounts.index', 'active' => 'backoffice.finance.accounts.*', 'icon' => 'wallet', 'model' => \App\Models\TenantFinancialAccount::class],
            ['label' => 'Prestações de renda', 'route' => 'backoffice.finance.installments.index', 'active' => 'backoffice.finance.installments.*', 'icon' => 'file', 'model' => \App\Models\RentInstallment::class],
            ['label' => 'Pagamentos de renda', 'route' => 'backoffice.finance.payments.index', 'active' => 'backoffice.finance.payments.*', 'icon' => 'wallet', 'model' => \App\Models\LeasePayment::class],
            ['label' => 'Incumprimentos', 'route' => 'backoffice.finance.arrears.index', 'active' => 'backoffice.finance.arrears.*', 'icon' => 'alert', 'model' => \App\Models\Arrear::class],
            ['label' => 'Revisões de renda', 'route' => 'backoffice.finance.rent-reviews.index', 'active' => 'backoffice.finance.rent-reviews.*', 'icon' => 'check', 'model' => \App\Models\RentReview::class],
        ],
        'Património' => [
            ['label' => 'Habitações', 'route' => 'housing-units.index', 'active' => 'housing-units.*', 'icon' => 'home'],
            ['label' => 'Portal público', 'route' => 'backoffice.public-portal.settings.edit', 'active' => 'backoffice.public-portal.*', 'icon' => 'dashboard', 'permission' => 'settings.view'],
            ['label' => 'Ligações públicas', 'route' => 'backoffice.public-portal.links.index', 'active' => 'backoffice.public-portal.links.*', 'icon' => 'document', 'permission' => 'settings.view'],
            ['label' => 'Contratos', 'route' => 'contracts.index', 'active' => 'contracts.*', 'icon' => 'file'],
            ['label' => 'Pagamentos', 'route' => 'payments.index', 'active' => 'payments.*', 'icon' => 'wallet'],
            ['label' => 'Manutenção', 'route' => 'backoffice.maintenance.index', 'active' => 'backoffice.maintenance.*', 'icon' => 'tool', 'model' => \App\Models\MaintenanceRequest::class],
            ['label' => 'Vistorias', 'route' => 'backoffice.inspections.index', 'active' => 'backoffice.inspections.*', 'icon' => 'check', 'model' => \App\Models\PropertyInspection::class],
        ],
        'Análise' => [
            ['label' => 'Relatórios', 'route' => 'backoffice.reports.index', 'active' => 'backoffice.reports.index', 'icon' => 'document', 'model' => \App\Models\ReportDefinition::class],
            ['label' => 'Painel operacional', 'route' => 'backoffice.reports.operational', 'active' => 'backoffice.reports.operational', 'icon' => 'dashboard', 'model' => \App\Models\DashboardDefinition::class],
            ['label' => 'Painel executivo', 'route' => 'backoffice.reports.executive', 'active' => 'backoffice.reports.executive', 'icon' => 'dashboard', 'model' => \App\Models\DashboardDefinition::class, 'permission' => 'reports.view_executive'],
            ['label' => 'Exportações', 'route' => 'backoffice.reports.exports.index', 'active' => 'backoffice.reports.exports.*', 'icon' => 'file', 'model' => \App\Models\ReportExport::class],
        ],
        'Segurança e RGPD' => [
            ['label' => 'Painel de segurança', 'route' => 'backoffice.security.dashboard', 'active' => 'backoffice.security.dashboard', 'icon' => 'dashboard', 'permission' => 'settings.view'],
            ['label' => 'Utilizadores', 'route' => 'backoffice.users.index', 'active' => 'backoffice.users.*', 'icon' => 'users', 'permission' => 'users.view'],
            ['label' => 'Perfis', 'route' => 'backoffice.roles.index', 'active' => 'backoffice.roles.*', 'icon' => 'check', 'permission' => 'roles.view'],
            ['label' => 'Equipas municipais', 'route' => 'backoffice.teams.index', 'active' => 'backoffice.teams.*', 'icon' => 'users', 'permission' => 'teams.view'],
            ['label' => 'Auditoria de acessos', 'route' => 'backoffice.access-audit.index', 'active' => 'backoffice.access-audit.*', 'icon' => 'document', 'permission' => 'access_audit.view'],
            ['label' => 'MFA', 'route' => 'backoffice.security.mfa.index', 'active' => 'backoffice.security.mfa.*', 'icon' => 'check', 'permission' => 'settings.view'],
            ['label' => 'Auditoria', 'route' => 'backoffice.security.audit.events.index', 'active' => 'backoffice.security.audit.*', 'icon' => 'document', 'permission' => 'audit_logs.view'],
            ['label' => 'Pedidos RGPD', 'route' => 'backoffice.security.privacy.requests.index', 'active' => 'backoffice.security.privacy.requests.*', 'icon' => 'file', 'permission' => 'privacy.view'],
            ['label' => 'Alertas', 'route' => 'backoffice.security.alerts.index', 'active' => 'backoffice.security.alerts.*', 'icon' => 'alert', 'permission' => 'settings.audit'],
            ['label' => 'Checklists', 'route' => 'backoffice.security.checklists.index', 'active' => 'backoffice.security.checklists.*', 'icon' => 'check', 'permission' => 'settings.audit'],
        ],
        'Comunicações' => [
            ['label' => 'Centro de comunicações', 'route' => 'backoffice.communications.index', 'active' => 'backoffice.communications.index', 'icon' => 'alert', 'model' => \App\Models\CommunicationLog::class],
            ['label' => 'Histórico', 'route' => 'backoffice.communications.logs.index', 'active' => 'backoffice.communications.logs.*', 'icon' => 'document', 'model' => \App\Models\CommunicationLog::class],
            ['label' => 'Modelos', 'route' => 'backoffice.communications.templates.index', 'active' => 'backoffice.communications.templates.*', 'icon' => 'file', 'model' => \App\Models\NotificationTemplate::class],
            ['label' => 'Regras por evento', 'route' => 'backoffice.communications.event-rules.index', 'active' => 'backoffice.communications.event-rules.*', 'icon' => 'check', 'model' => \App\Models\NotificationEventRule::class],
            ['label' => 'Variáveis', 'route' => 'backoffice.communications.variables.index', 'active' => 'backoffice.communications.variables.*', 'icon' => 'document', 'model' => \App\Models\TemplateVariable::class],
            ['label' => 'Modelos documentais', 'route' => 'backoffice.document-templates.index', 'active' => 'backoffice.document-templates.*', 'icon' => 'document', 'model' => \App\Models\DocumentTemplate::class],
            ['label' => 'Documentos oficiais', 'route' => 'backoffice.official-documents.index', 'active' => 'backoffice.official-documents.*', 'icon' => 'file', 'model' => \App\Models\GeneratedOfficialDocument::class],
        ],
    ];

    if (! $isCandidate) {
        $route = request()->route();
        $workspaceParam = $route?->parameter('workspace');
        $workspaceKey = is_string($workspaceParam) ? $workspaceParam : null;
        $currentWorkspace = app(\App\Services\Navigation\WorkspaceResolver::class)
            ->resolve($user, $route?->getName(), $workspaceKey);
        $navigationGroups = app(\App\Services\Navigation\WorkspaceService::class)
            ->navigationGroups($user, is_array($currentWorkspace) ? (string) $currentWorkspace['key'] : null);
    }
@endphp

<div class="lg:hidden">
    <div class="flex h-16 items-center justify-between border-b border-ink-100 bg-white px-4">
        <a href="{{ route($homeRoute) }}" class="flex items-center gap-3">
            <x-application-logo class="block h-9 w-auto fill-current text-civic-700" />
            <div>
                <p class="text-sm font-bold text-ink-900">MV HAB</p>
                <p class="text-xs font-medium text-ink-500">Habitação municipal</p>
            </div>
        </a>

        <button type="button" class="rounded-md border border-ink-100 bg-white p-2 text-ink-700" @click="sidebarOpen = true" aria-label="Abrir navegação">
            <x-ui-icon name="menu" class="h-5 w-5" />
        </button>
    </div>
</div>

<div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-ink-900/40" @click="sidebarOpen = false"></div>

    <aside class="fixed inset-y-0 left-0 flex w-80 max-w-[85vw] flex-col border-r border-ink-100 bg-white">
        <div class="flex h-16 items-center justify-between border-b border-ink-100 px-4">
            <a href="{{ route($homeRoute) }}" class="flex items-center gap-3">
                <x-application-logo class="block h-9 w-auto fill-current text-civic-700" />
                <div>
                    <p class="text-sm font-bold text-ink-900">MV HAB</p>
                    <p class="text-xs font-medium text-ink-500">Habitação municipal</p>
                </div>
            </a>

            <button type="button" class="rounded-md border border-ink-100 bg-white p-2 text-ink-700" @click="sidebarOpen = false" aria-label="Fechar navegação">
                <x-ui-icon name="close" class="h-5 w-5" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-5">
            <x-navigation.context-sidebar :groups="$navigationGroups" responsive />
        </div>

        <div class="border-t border-ink-100 p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-md bg-civic-50 text-civic-700">
                    <x-ui-icon name="user" class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-ink-900">{{ $user->name }}</p>
                    <p class="truncate text-xs text-ink-500">{{ $user->email }}</p>
                </div>
            </div>

            @if ($isCandidate)
                <div class="mt-4 space-y-1">
                    @foreach ($navigationFooterLinks as $link)
                        <a href="{{ route($link['route']) }}" class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-ink-600 transition hover:bg-ink-50 hover:text-ink-900">
                            <x-ui-icon :name="$link['icon']" class="h-4 w-4 shrink-0" />
                            <span>{{ $link['label'] }}</span>
                        </a>
                    @endforeach

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm font-medium text-ink-600 transition hover:bg-ink-50 hover:text-ink-900">
                            <x-ui-icon name="alert" class="h-4 w-4 shrink-0" />
                            <span>Terminar sessão</span>
                        </button>
                    </form>
                </div>
            @else
                <div class="mt-4 grid gap-2">
                    <a href="{{ route('public.portal') }}" class="mv-button-secondary">Portal público</a>
                    <a href="{{ route('profile.edit') }}" class="mv-button-secondary">Perfil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="mv-button-secondary w-full">Terminar sessão</button>
                    </form>
                </div>
            @endif
        </div>
    </aside>
</div>

<aside class="fixed inset-y-0 left-0 hidden w-72 flex-col border-r border-ink-100 bg-white lg:flex">
    <div class="flex h-20 items-center border-b border-ink-100 px-6">
        <a href="{{ route($homeRoute) }}" class="flex items-center gap-3">
            <x-application-logo class="block h-10 w-auto fill-current text-civic-700" />
            <div>
                <p class="text-base font-bold text-ink-900">MV HAB</p>
                <p class="text-xs font-medium text-ink-500">Plataforma municipal</p>
            </div>
        </a>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-6">
        <x-navigation.context-sidebar :groups="$navigationGroups" />
    </div>

    <div class="border-t border-ink-100 p-4">
        @if ($isCandidate)
            <div class="flex items-center gap-3 rounded-md border border-ink-100 bg-white px-3 py-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-md bg-civic-50 text-civic-700">
                    <x-ui-icon name="user" class="h-5 w-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-ink-900">{{ $user->name }}</span>
                    <span class="block truncate text-xs text-ink-500">{{ $user->email }}</span>
                </span>
            </div>

            <div class="mt-4 space-y-1">
                @foreach ($navigationFooterLinks as $link)
                    <a href="{{ route($link['route']) }}" class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-ink-600 transition hover:bg-ink-50 hover:text-ink-900">
                        <x-ui-icon :name="$link['icon']" class="h-4 w-4 shrink-0" />
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm font-medium text-ink-600 transition hover:bg-ink-50 hover:text-ink-900">
                        <x-ui-icon name="alert" class="h-4 w-4 shrink-0" />
                        <span>Terminar sessão</span>
                    </button>
                </form>
            </div>
        @else
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex w-full items-center gap-3 rounded-md border border-ink-100 bg-white px-3 py-3 text-left transition hover:bg-ink-50">
                    <span class="flex h-10 w-10 items-center justify-center rounded-md bg-civic-50 text-civic-700">
                        <x-ui-icon name="user" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-ink-900">{{ $user->name }}</span>
                        <span class="block truncate text-xs text-ink-500">{{ $user->email }}</span>
                    </span>
                    <x-ui-icon name="arrow" class="h-4 w-4 text-ink-500" />
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('public.portal')">
                        Portal público
                    </x-dropdown-link>

                    <x-dropdown-link :href="route('profile.edit')">
                        Perfil
                    </x-dropdown-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                            Terminar sessão
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        @endif
    </div>
</aside>
