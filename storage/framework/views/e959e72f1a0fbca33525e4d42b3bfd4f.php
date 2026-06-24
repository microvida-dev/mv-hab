<?php
    $isCandidate = Auth::user()->hasRole('candidate');
    $homeRoute = $isCandidate ? 'candidate.dashboard' : 'dashboard';

    $navigationGroups = $isCandidate ? [
        'Área pessoal' => [
            ['label' => 'Visão geral', 'route' => 'candidate.dashboard', 'active' => 'candidate.dashboard', 'icon' => 'dashboard'],
            ['label' => 'O meu registo', 'route' => 'candidate.registration.show', 'active' => 'candidate.registration.*', 'icon' => 'user'],
            ['label' => 'Agregado', 'route' => 'candidate.household.show', 'active' => 'candidate.household*', 'icon' => 'users'],
            ['label' => 'Rendimentos', 'route' => 'candidate.income-records.index', 'active' => 'candidate.income-records.*', 'icon' => 'wallet'],
            ['label' => 'Habitação atual', 'route' => 'candidate.current-housing.show', 'active' => 'candidate.current-housing.*', 'icon' => 'home'],
            ['label' => 'Simulações', 'route' => 'candidate.simulations.index', 'active' => 'candidate.simulations.*', 'icon' => 'check'],
            ['label' => 'Renovações', 'route' => 'candidate.registration-renewals.index', 'active' => 'candidate.registration-renewals.*', 'icon' => 'document'],
            ['label' => 'Elegibilidade', 'route' => 'candidate.eligibility.index', 'active' => 'candidate.eligibility.*', 'icon' => 'check'],
            ['label' => 'Candidaturas', 'route' => 'candidate.applications.index', 'active' => 'candidate.applications.*', 'icon' => 'file'],
            ['label' => 'Visitas', 'route' => 'candidate.visits.index', 'active' => 'candidate.visits.*', 'icon' => 'home'],
            ['label' => 'Apoio', 'route' => 'candidate.support-tickets.index', 'active' => 'candidate.support-tickets.*', 'icon' => 'alert'],
            ['label' => 'Interações', 'route' => 'candidate.interactions.index', 'active' => 'candidate.interactions.*', 'icon' => 'document'],
            ['label' => 'FAQ contextual', 'route' => 'candidate.contextual-faq.index', 'active' => 'candidate.contextual-faq.*', 'icon' => 'check'],
            ['label' => 'Processos', 'route' => 'candidate.processes.index', 'active' => 'candidate.processes.*', 'icon' => 'file'],
            ['label' => 'Aperfeiçoamento', 'route' => 'candidate.correction-requests.index', 'active' => 'candidate.correction-requests.*', 'icon' => 'alert'],
            ['label' => 'Documentos', 'route' => 'candidate.documents.index', 'active' => 'candidate.documents.*', 'icon' => 'document'],
            ['label' => 'Preferências', 'route' => 'candidate.housing-preferences.index', 'active' => 'candidate.housing-preferences.*', 'icon' => 'home'],
            ['label' => 'Ofertas', 'route' => 'candidate.allocation-offers.index', 'active' => 'candidate.allocation-offers.*', 'icon' => 'file'],
            ['label' => 'Atribuições', 'route' => 'candidate.allocations.index', 'active' => 'candidate.allocations.*', 'icon' => 'check'],
            ['label' => 'Área do inquilino', 'route' => 'tenant.dashboard', 'active' => 'tenant.*', 'icon' => 'home'],
            ['label' => 'Contratos', 'route' => 'candidate.contracts.index', 'active' => 'candidate.contracts.*', 'icon' => 'document'],
            ['label' => 'Financeiro', 'route' => 'candidate.finance.index', 'active' => 'candidate.finance.*', 'icon' => 'wallet'],
            ['label' => 'Manutenção', 'route' => 'candidate.maintenance.index', 'active' => 'candidate.maintenance.*', 'icon' => 'tool'],
            ['label' => 'Vistorias', 'route' => 'candidate.inspections.index', 'active' => 'candidate.inspections.*', 'icon' => 'check'],
            ['label' => 'Notificações', 'route' => 'candidate.notifications.index', 'active' => 'candidate.notifications.*', 'icon' => 'alert'],
            ['label' => 'Comunicações', 'route' => 'candidate.communications.index', 'active' => 'candidate.communications.*', 'icon' => 'document'],
            ['label' => 'Documentos oficiais', 'route' => 'candidate.official-documents.index', 'active' => 'candidate.official-documents.*', 'icon' => 'file'],
            ['label' => 'Privacidade RGPD', 'route' => 'candidate.privacy.index', 'active' => 'candidate.privacy.*', 'icon' => 'check'],
            ['label' => 'Preferências de contacto', 'route' => 'candidate.notification-preferences.edit', 'active' => 'candidate.notification-preferences.*', 'icon' => 'user'],
            ['label' => 'Perfil da conta', 'route' => 'candidate.profile', 'active' => 'profile.*', 'icon' => 'user'],
        ],
    ] : [
        'Operação' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'dashboard'],
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
            ['label' => 'Insights do simulador', 'route' => 'backoffice.simulator.insights.index', 'active' => 'backoffice.simulator.*', 'icon' => 'dashboard', 'permission' => 'simulator.view'],
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
            ['label' => 'Disponibilidade de visitas', 'route' => 'backoffice.visit-availabilities.index', 'active' => 'backoffice.visit-availabilities.*', 'icon' => 'home', 'model' => \App\Models\VisitAvailability::class],
            ['label' => 'Calendário de visitas', 'route' => 'backoffice.visit-slots.index', 'active' => 'backoffice.visit-slots.*', 'icon' => 'check', 'model' => \App\Models\VisitSlot::class],
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
            ['label' => 'Dashboard operacional', 'route' => 'backoffice.reports.operational', 'active' => 'backoffice.reports.operational', 'icon' => 'dashboard', 'model' => \App\Models\DashboardDefinition::class],
            ['label' => 'Dashboard executivo', 'route' => 'backoffice.reports.executive', 'active' => 'backoffice.reports.executive', 'icon' => 'dashboard', 'model' => \App\Models\DashboardDefinition::class, 'permission' => 'reports.view_executive'],
            ['label' => 'Exportações', 'route' => 'backoffice.reports.exports.index', 'active' => 'backoffice.reports.exports.*', 'icon' => 'file', 'model' => \App\Models\ReportExport::class],
        ],
        'Segurança e RGPD' => [
            ['label' => 'Painel de segurança', 'route' => 'backoffice.security.dashboard', 'active' => 'backoffice.security.dashboard', 'icon' => 'dashboard', 'permission' => 'settings.view'],
            ['label' => 'MFA', 'route' => 'backoffice.security.mfa.index', 'active' => 'backoffice.security.mfa.*', 'icon' => 'check', 'permission' => 'settings.view'],
            ['label' => 'Audit trail', 'route' => 'backoffice.security.audit.events.index', 'active' => 'backoffice.security.audit.*', 'icon' => 'document', 'permission' => 'audit_logs.view'],
            ['label' => 'Pedidos RGPD', 'route' => 'backoffice.security.privacy.requests.index', 'active' => 'backoffice.security.privacy.requests.*', 'icon' => 'file', 'permission' => 'privacy.view'],
            ['label' => 'Alertas', 'route' => 'backoffice.security.alerts.index', 'active' => 'backoffice.security.alerts.*', 'icon' => 'alert', 'permission' => 'settings.audit'],
            ['label' => 'Checklists', 'route' => 'backoffice.security.checklists.index', 'active' => 'backoffice.security.checklists.*', 'icon' => 'check', 'permission' => 'settings.audit'],
        ],
        'Comunicações' => [
            ['label' => 'Centro de comunicações', 'route' => 'backoffice.communications.index', 'active' => 'backoffice.communications.index', 'icon' => 'alert', 'model' => \App\Models\CommunicationLog::class],
            ['label' => 'Histórico', 'route' => 'backoffice.communications.logs.index', 'active' => 'backoffice.communications.logs.*', 'icon' => 'document', 'model' => \App\Models\CommunicationLog::class],
            ['label' => 'Templates', 'route' => 'backoffice.communications.templates.index', 'active' => 'backoffice.communications.templates.*', 'icon' => 'file', 'model' => \App\Models\NotificationTemplate::class],
            ['label' => 'Regras por evento', 'route' => 'backoffice.communications.event-rules.index', 'active' => 'backoffice.communications.event-rules.*', 'icon' => 'check', 'model' => \App\Models\NotificationEventRule::class],
            ['label' => 'Variáveis', 'route' => 'backoffice.communications.variables.index', 'active' => 'backoffice.communications.variables.*', 'icon' => 'document', 'model' => \App\Models\TemplateVariable::class],
            ['label' => 'Modelos documentais', 'route' => 'backoffice.document-templates.index', 'active' => 'backoffice.document-templates.*', 'icon' => 'document', 'model' => \App\Models\DocumentTemplate::class],
            ['label' => 'Documentos oficiais', 'route' => 'backoffice.official-documents.index', 'active' => 'backoffice.official-documents.*', 'icon' => 'file', 'model' => \App\Models\GeneratedOfficialDocument::class],
        ],
    ];
?>

<div class="lg:hidden">
    <div class="flex h-16 items-center justify-between border-b border-ink-100 bg-white px-4">
        <a href="<?php echo e(route($homeRoute)); ?>" class="flex items-center gap-3">
            <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['class' => 'block h-9 w-auto fill-current text-civic-700']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'block h-9 w-auto fill-current text-civic-700']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $attributes = $__attributesOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $component = $__componentOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__componentOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
            <div>
                <p class="text-sm font-bold text-ink-900">MV HAB</p>
                <p class="text-xs font-medium text-ink-500">Habitação municipal</p>
            </div>
        </a>

        <button type="button" class="rounded-md border border-ink-100 bg-white p-2 text-ink-700" @click="sidebarOpen = true" aria-label="Abrir navegação">
            <?php if (isset($component)) { $__componentOriginalaa25fa354301adf40df60a26b9586efa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa25fa354301adf40df60a26b9586efa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui-icon','data' => ['name' => 'menu','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'menu','class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $attributes = $__attributesOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__attributesOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $component = $__componentOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__componentOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
        </button>
    </div>
</div>

<div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-ink-900/40" @click="sidebarOpen = false"></div>

    <aside class="fixed inset-y-0 left-0 flex w-80 max-w-[85vw] flex-col border-r border-ink-100 bg-white">
        <div class="flex h-16 items-center justify-between border-b border-ink-100 px-4">
            <a href="<?php echo e(route($homeRoute)); ?>" class="flex items-center gap-3">
                <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['class' => 'block h-9 w-auto fill-current text-civic-700']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'block h-9 w-auto fill-current text-civic-700']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $attributes = $__attributesOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $component = $__componentOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__componentOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
                <div>
                    <p class="text-sm font-bold text-ink-900">MV HAB</p>
                    <p class="text-xs font-medium text-ink-500">Habitação municipal</p>
                </div>
            </a>

            <button type="button" class="rounded-md border border-ink-100 bg-white p-2 text-ink-700" @click="sidebarOpen = false" aria-label="Fechar navegação">
                <?php if (isset($component)) { $__componentOriginalaa25fa354301adf40df60a26b9586efa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa25fa354301adf40df60a26b9586efa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui-icon','data' => ['name' => 'close','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'close','class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $attributes = $__attributesOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__attributesOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $component = $__componentOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__componentOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-5">
            <?php $__currentLoopData = $navigationGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group => $links): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="<?php echo e($loop->first ? '' : 'mt-6'); ?>">
                    <p class="px-3 text-xs font-semibold uppercase text-ink-500"><?php echo e($group); ?></p>
                    <div class="mt-2 space-y-1">
                        <?php $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if((! isset($link['model']) || Auth::user()->can('viewAny', $link['model'])) && (! isset($link['permission']) || Auth::user()->hasPermission($link['permission']))): ?>
                                <?php if (isset($component)) { $__componentOriginald69b52d99510f1e7cd3d80070b28ca18 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald69b52d99510f1e7cd3d80070b28ca18 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.responsive-nav-link','data' => ['href' => route($link['route']),'active' => request()->routeIs($link['active'])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('responsive-nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route($link['route'])),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs($link['active']))]); ?>
                                    <?php if (isset($component)) { $__componentOriginalaa25fa354301adf40df60a26b9586efa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa25fa354301adf40df60a26b9586efa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui-icon','data' => ['name' => $link['icon'],'class' => 'h-4 w-4 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($link['icon']),'class' => 'h-4 w-4 shrink-0']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $attributes = $__attributesOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__attributesOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $component = $__componentOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__componentOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
                                    <span><?php echo e($link['label']); ?></span>
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald69b52d99510f1e7cd3d80070b28ca18)): ?>
<?php $attributes = $__attributesOriginald69b52d99510f1e7cd3d80070b28ca18; ?>
<?php unset($__attributesOriginald69b52d99510f1e7cd3d80070b28ca18); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald69b52d99510f1e7cd3d80070b28ca18)): ?>
<?php $component = $__componentOriginald69b52d99510f1e7cd3d80070b28ca18; ?>
<?php unset($__componentOriginald69b52d99510f1e7cd3d80070b28ca18); ?>
<?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="border-t border-ink-100 p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-md bg-civic-50 text-civic-700">
                    <?php if (isset($component)) { $__componentOriginalaa25fa354301adf40df60a26b9586efa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa25fa354301adf40df60a26b9586efa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui-icon','data' => ['name' => 'user','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'user','class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $attributes = $__attributesOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__attributesOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $component = $__componentOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__componentOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-ink-900"><?php echo e(Auth::user()->name); ?></p>
                    <p class="truncate text-xs text-ink-500"><?php echo e(Auth::user()->email); ?></p>
                </div>
            </div>

            <div class="mt-4 grid gap-2">
                <a href="<?php echo e(route('public.portal')); ?>" class="mv-button-secondary">Portal público</a>
                <a href="<?php echo e(route('profile.edit')); ?>" class="mv-button-secondary">Perfil</a>
                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="mv-button-secondary w-full">Terminar sessão</button>
                </form>
            </div>
        </div>
    </aside>
</div>

<aside class="fixed inset-y-0 left-0 hidden w-72 flex-col border-r border-ink-100 bg-white lg:flex">
    <div class="flex h-20 items-center border-b border-ink-100 px-6">
        <a href="<?php echo e(route($homeRoute)); ?>" class="flex items-center gap-3">
            <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['class' => 'block h-10 w-auto fill-current text-civic-700']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'block h-10 w-auto fill-current text-civic-700']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $attributes = $__attributesOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $component = $__componentOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__componentOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
            <div>
                <p class="text-base font-bold text-ink-900">MV HAB</p>
                <p class="text-xs font-medium text-ink-500">Plataforma municipal</p>
            </div>
        </a>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-6">
        <?php $__currentLoopData = $navigationGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group => $links): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="<?php echo e($loop->first ? '' : 'mt-7'); ?>">
                <p class="px-3 text-xs font-semibold uppercase text-ink-500"><?php echo e($group); ?></p>
                <div class="mt-2 space-y-1">
                    <?php $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if((! isset($link['model']) || Auth::user()->can('viewAny', $link['model'])) && (! isset($link['permission']) || Auth::user()->hasPermission($link['permission']))): ?>
                            <?php if (isset($component)) { $__componentOriginalc295f12dca9d42f28a259237a5724830 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc295f12dca9d42f28a259237a5724830 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.nav-link','data' => ['href' => route($link['route']),'active' => request()->routeIs($link['active'])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route($link['route'])),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs($link['active']))]); ?>
                                <?php if (isset($component)) { $__componentOriginalaa25fa354301adf40df60a26b9586efa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa25fa354301adf40df60a26b9586efa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui-icon','data' => ['name' => $link['icon'],'class' => 'h-4 w-4 shrink-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($link['icon']),'class' => 'h-4 w-4 shrink-0']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $attributes = $__attributesOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__attributesOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $component = $__componentOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__componentOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
                                <span><?php echo e($link['label']); ?></span>
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc295f12dca9d42f28a259237a5724830)): ?>
<?php $attributes = $__attributesOriginalc295f12dca9d42f28a259237a5724830; ?>
<?php unset($__attributesOriginalc295f12dca9d42f28a259237a5724830); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc295f12dca9d42f28a259237a5724830)): ?>
<?php $component = $__componentOriginalc295f12dca9d42f28a259237a5724830; ?>
<?php unset($__componentOriginalc295f12dca9d42f28a259237a5724830); ?>
<?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="border-t border-ink-100 p-4">
        <?php if (isset($component)) { $__componentOriginaldf8083d4a852c446488d8d384bbc7cbe = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dropdown','data' => ['align' => 'right','width' => '48']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['align' => 'right','width' => '48']); ?>
             <?php $__env->slot('trigger', null, []); ?> 
                <button class="flex w-full items-center gap-3 rounded-md border border-ink-100 bg-white px-3 py-3 text-left transition hover:bg-ink-50">
                    <span class="flex h-10 w-10 items-center justify-center rounded-md bg-civic-50 text-civic-700">
                        <?php if (isset($component)) { $__componentOriginalaa25fa354301adf40df60a26b9586efa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa25fa354301adf40df60a26b9586efa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui-icon','data' => ['name' => 'user','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'user','class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $attributes = $__attributesOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__attributesOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $component = $__componentOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__componentOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-ink-900"><?php echo e(Auth::user()->name); ?></span>
                        <span class="block truncate text-xs text-ink-500"><?php echo e(Auth::user()->email); ?></span>
                    </span>
                    <?php if (isset($component)) { $__componentOriginalaa25fa354301adf40df60a26b9586efa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa25fa354301adf40df60a26b9586efa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui-icon','data' => ['name' => 'arrow','class' => 'h-4 w-4 text-ink-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow','class' => 'h-4 w-4 text-ink-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $attributes = $__attributesOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__attributesOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa25fa354301adf40df60a26b9586efa)): ?>
<?php $component = $__componentOriginalaa25fa354301adf40df60a26b9586efa; ?>
<?php unset($__componentOriginalaa25fa354301adf40df60a26b9586efa); ?>
<?php endif; ?>
                </button>
             <?php $__env->endSlot(); ?>

             <?php $__env->slot('content', null, []); ?> 
                <?php if (isset($component)) { $__componentOriginal68cb1971a2b92c9735f83359058f7108 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal68cb1971a2b92c9735f83359058f7108 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dropdown-link','data' => ['href' => route('public.portal')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dropdown-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('public.portal'))]); ?>
                    Portal público
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal68cb1971a2b92c9735f83359058f7108)): ?>
<?php $attributes = $__attributesOriginal68cb1971a2b92c9735f83359058f7108; ?>
<?php unset($__attributesOriginal68cb1971a2b92c9735f83359058f7108); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal68cb1971a2b92c9735f83359058f7108)): ?>
<?php $component = $__componentOriginal68cb1971a2b92c9735f83359058f7108; ?>
<?php unset($__componentOriginal68cb1971a2b92c9735f83359058f7108); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginal68cb1971a2b92c9735f83359058f7108 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal68cb1971a2b92c9735f83359058f7108 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dropdown-link','data' => ['href' => route('profile.edit')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dropdown-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('profile.edit'))]); ?>
                    Perfil
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal68cb1971a2b92c9735f83359058f7108)): ?>
<?php $attributes = $__attributesOriginal68cb1971a2b92c9735f83359058f7108; ?>
<?php unset($__attributesOriginal68cb1971a2b92c9735f83359058f7108); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal68cb1971a2b92c9735f83359058f7108)): ?>
<?php $component = $__componentOriginal68cb1971a2b92c9735f83359058f7108; ?>
<?php unset($__componentOriginal68cb1971a2b92c9735f83359058f7108); ?>
<?php endif; ?>

                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <?php if (isset($component)) { $__componentOriginal68cb1971a2b92c9735f83359058f7108 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal68cb1971a2b92c9735f83359058f7108 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dropdown-link','data' => ['href' => route('logout'),'onclick' => 'event.preventDefault(); this.closest(\'form\').submit();']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dropdown-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('logout')),'onclick' => 'event.preventDefault(); this.closest(\'form\').submit();']); ?>
                        Terminar sessão
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal68cb1971a2b92c9735f83359058f7108)): ?>
<?php $attributes = $__attributesOriginal68cb1971a2b92c9735f83359058f7108; ?>
<?php unset($__attributesOriginal68cb1971a2b92c9735f83359058f7108); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal68cb1971a2b92c9735f83359058f7108)): ?>
<?php $component = $__componentOriginal68cb1971a2b92c9735f83359058f7108; ?>
<?php unset($__componentOriginal68cb1971a2b92c9735f83359058f7108); ?>
<?php endif; ?>
                </form>
             <?php $__env->endSlot(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe)): ?>
<?php $attributes = $__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe; ?>
<?php unset($__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldf8083d4a852c446488d8d384bbc7cbe)): ?>
<?php $component = $__componentOriginaldf8083d4a852c446488d8d384bbc7cbe; ?>
<?php unset($__componentOriginaldf8083d4a852c446488d8d384bbc7cbe); ?>
<?php endif; ?>
    </div>
</aside>
<?php /**PATH /Users/brunocorreia/Documents/CRM HAB/MV-HAB/resources/views/layouts/navigation.blade.php ENDPATH**/ ?>