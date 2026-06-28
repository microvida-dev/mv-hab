<?php

namespace App\Services\Dashboard;

use App\Models\User;

class DashboardQuickActionService
{
    public function __construct(private readonly DashboardAuthorizationService $authorization) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forUser(User $user): array
    {
        $actions = [];

        foreach ($this->authorization->profileKeys($user) as $profile) {
            $actions = array_merge($actions, $this->actionsForProfile($profile));
        }

        return collect($actions)
            ->unique(fn (array $action): string => (string) $action['route'])
            ->filter(fn (array $action): bool => $this->authorization->canSeeItem($user, $action))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function actionsForProfile(string $profile): array
    {
        return match ($profile) {
            'administrator' => [
                $this->action('Gerir utilizadores', 'backoffice.users.index', 'users.view', 'Administração de acessos municipais.'),
                $this->action('Rever segurança', 'backoffice.security.dashboard', null, 'Alertas, MFA e RGPD operacional.', ['administrator', 'auditor']),
                $this->action('Relatórios municipais', 'backoffice.reports.index', 'reports.view', 'KPIs e reporting autorizado.'),
                $this->action('Tarefas', 'backoffice.work-tasks.dashboard', 'work_tasks.dashboard', 'Carga operacional e SLA.'),
            ],
            'municipal_technician' => [
                $this->action('Rever documentos', 'admin.document-reviews.index', 'documents.view', 'Documentos pendentes e correções.'),
                $this->action('Abrir candidaturas', 'backoffice.applications.index', 'applications.view', 'Candidaturas submetidas e em análise.'),
                $this->action('Ver tarefas', 'backoffice.work-tasks.my', 'work_tasks.view', 'Fila pessoal e SLA.'),
                $this->action('Ver concursos', 'admin.contests.index', 'contests.view', 'Concursos e critérios configurados.'),
            ],
            'jury' => [
                $this->action('Classificar processos', 'backoffice.scoring.rule-sets.index', 'scoring.view', 'Critérios e execução de classificação.'),
                $this->action('Ver listas', 'backoffice.lists.provisional.index', 'public_lists.view', 'Listas provisórias e revisão.'),
                $this->action('Ver reclamações', 'backoffice.complaints.index', 'complaints.view', 'Reclamações e audiência prévia.'),
            ],
            'legal_manager' => [
                $this->action('Rever contratos', 'backoffice.contracts.leases.index', 'contracts.view', 'Contratos pendentes de validação.'),
                $this->action('Reclamações jurídicas', 'backoffice.complaints.index', 'complaints.view', 'Reclamações e decisões jurídicas.'),
                $this->action('Audiência prévia', 'backoffice.hearings.index', 'complaints.view', 'Pronúncias e prazos.'),
            ],
            'financial_manager' => [
                $this->action('Ver rendas', 'backoffice.finance.accounts.index', 'finance.view', 'Contas e prestações manuais.'),
                $this->action('Ver pagamentos', 'backoffice.finance.payments.index', 'payments.view', 'Registos financeiros internos.'),
                $this->action('Ver contratos', 'backoffice.contracts.leases.index', 'contracts.view', 'Contratos com impacto financeiro.'),
            ],
            'housing_manager' => [
                $this->action('Ver fogos', 'housing-units.index', 'housing_units.view', 'Parque habitacional e disponibilidade.'),
                $this->action('Ver contratos', 'backoffice.contracts.leases.index', 'contracts.view', 'Contratos operacionais.'),
                $this->action('Ver visitas', 'backoffice.housing-visits.index', 'visits.view', 'Visitas a fogos publicáveis.'),
            ],
            'maintenance_manager' => [
                $this->action('Pedidos urgentes', 'backoffice.maintenance.index', 'maintenance_requests.view', 'Pedidos de manutenção prioritários.'),
                $this->action('Ver vistorias', 'backoffice.inspections.index', 'inspections.view', 'Agenda técnica e autos.'),
                $this->action('Tarefas vencidas', 'backoffice.work-tasks.overdue', 'work_tasks.view', 'SLA operacional por regularizar.'),
            ],
            'inspection_manager' => [
                $this->action('Vistorias agendadas', 'backoffice.inspections.index', 'inspections.view', 'Vistorias e relatórios técnicos.'),
                $this->action('Histórico de imóveis', 'backoffice.maintenance.index', 'maintenance_requests.view', 'Pedidos e intervenções por imóvel.'),
                $this->action('Tarefas de vistoria', 'backoffice.work-tasks.my', 'work_tasks.view', 'Fila pessoal de vistorias.'),
            ],
            'support_agent' => [
                $this->action('Tickets abertos', 'backoffice.support-tickets.index', 'support.view', 'Atendimento e pedidos do candidato.'),
                $this->action('Visitas marcadas', 'backoffice.housing-visits.index', 'visits.view', 'Agendamento e reagendamento.'),
                $this->action('FAQ operacional', 'backoffice.contextual-faqs.index', 'contextual_faqs.view', 'Perguntas frequentes e suporte.'),
            ],
            'auditor' => [
                $this->action('Ver auditoria', 'backoffice.security.audit.events.index', 'audit_logs.view', 'Eventos críticos e rastreabilidade.'),
                $this->action('Acessos sensíveis', 'backoffice.security.audit.access-logs.index', 'audit_logs.view', 'Logs de acesso e consulta.'),
                $this->action('Relatórios', 'backoffice.reports.index', 'reports.view', 'Reporting autorizado em leitura.'),
            ],
            default => [],
        };
    }

    /**
     * @param  list<string>|null  $roles
     * @return array<string, mixed>
     */
    private function action(string $label, string $route, ?string $permission, string $description, ?array $roles = null): array
    {
        return array_filter([
            'label' => $label,
            'route' => $route,
            'permission' => $permission,
            'description' => $description,
            'roles' => $roles,
        ], fn (mixed $value): bool => $value !== null);
    }
}
