<?php

namespace App\Services\Dashboard;

use App\Models\DocumentSubmission;
use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Support\Facades\Route;

class DashboardWidgetRegistry
{
    public function __construct(private readonly DashboardAuthorizationService $authorization) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forUser(User $user): array
    {
        $panels = [];

        foreach ($this->authorization->profileKeys($user) as $profile) {
            $panels = array_merge($panels, $this->panelsForProfile($profile, $user));
        }

        return collect($panels)
            ->unique('key')
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function panelsForProfile(string $profile, User $user): array
    {
        return match ($profile) {
            'administrator' => [
                $this->panel(
                    key: 'admin_security',
                    title: 'Administração e segurança',
                    description: 'Utilizadores, equipas, MFA, alertas e auditoria.',
                    icon: 'security',
                    value: User::query()->where('status', 'active')->count(),
                    meta: 'Utilizadores ativos',
                    tone: 'info',
                    priority: 'medium',
                    href: $this->routeIfExists('backoffice.access.users.index'),
                    cta: 'Gerir acessos',
                ),
                $this->panel(
                    key: 'admin_operations',
                    title: 'Operação transversal',
                    description: 'Tarefas críticas, relatórios e riscos operacionais.',
                    icon: 'dashboard',
                    value: WorkTask::query()->where('due_at', '<', now())->count(),
                    meta: 'Tarefas vencidas',
                    tone: 'warning',
                    priority: 'high',
                    href: $this->routeIfExists('backoffice.work-tasks.index'),
                    cta: 'Abrir tarefas',
                ),
            ],

            'municipal_technician' => [
                $this->panel(
                    key: 'technical_review',
                    title: 'Revisão técnica',
                    description: 'Candidaturas, documentos, aperfeiçoamentos e SLA.',
                    icon: 'document',
                    value: DocumentSubmission::query()->where('status', 'submitted')->count(),
                    meta: 'Documentos pendentes',
                    tone: DocumentSubmission::query()->where('status', 'submitted')->exists() ? 'warning' : 'success',
                    priority: DocumentSubmission::query()->where('status', 'submitted')->exists() ? 'high' : 'low',
                    href: $this->routeIfExists('admin.document-reviews.index'),
                    cta: 'Abrir revisão',
                    badges: [
                        ['label' => 'SLA', 'tone' => 'warning'],
                    ],
                ),
            ],

            'jury' => [
                $this->panel(
                    key: 'jury_decision',
                    title: 'Classificação e listas',
                    description: 'Processos para pontuação, reclamações e publicações.',
                    icon: 'contest',
                    value: null,
                    meta: 'Processos de decisão',
                    tone: 'primary',
                    priority: 'medium',
                    href: $this->routeIfExists('backoffice.scoring.application-scores.index'),
                    cta: 'Abrir classificação',
                ),
            ],

            'legal_manager' => [
                $this->panel(
                    key: 'legal_review',
                    title: 'Validação jurídica',
                    description: 'Contratos, reclamações, audiência prévia e pareceres.',
                    icon: 'contract',
                    value: null,
                    meta: 'Validação jurídica',
                    tone: 'info',
                    priority: 'medium',
                    href: $this->routeIfExists('backoffice.contracts.leases.index'),
                    cta: 'Abrir contratos',
                ),
            ],

            'financial_manager' => [
                $this->panel(
                    key: 'financial_control',
                    title: 'Controlo financeiro',
                    description: 'Rendas manuais, pagamentos e contratos com impacto financeiro.',
                    icon: 'payment',
                    value: null,
                    meta: 'Registos financeiros',
                    tone: 'info',
                    priority: 'medium',
                    href: $this->routeIfExists('backoffice.finance.payments.index'),
                    cta: 'Abrir financeiro',
                ),
            ],

            'housing_manager' => [
                $this->panel(
                    key: 'housing_operations',
                    title: 'Gestão habitacional',
                    description: 'Fogos, ocupação, contratos operacionais e visitas.',
                    icon: 'housing',
                    value: null,
                    meta: 'Património habitacional',
                    tone: 'primary',
                    priority: 'medium',
                    href: $this->routeIfExists('backoffice.public-portal.housing-units.index'),
                    cta: 'Abrir fogos',
                ),
            ],

            'maintenance_manager' => [
                $this->panel(
                    key: 'maintenance_operations',
                    title: 'Manutenção',
                    description: 'Pedidos urgentes, intervenções abertas e tarefas vencidas.',
                    icon: 'maintenance',
                    value: null,
                    meta: 'Pedidos operacionais',
                    tone: 'warning',
                    priority: 'high',
                    href: $this->routeIfExists('backoffice.maintenance.index'),
                    cta: 'Abrir manutenção',
                ),
            ],

            'inspection_manager' => [
                $this->panel(
                    key: 'inspection_operations',
                    title: 'Vistorias',
                    description: 'Agenda, autos pendentes e histórico técnico.',
                    icon: 'inspection',
                    value: null,
                    meta: 'Agenda técnica',
                    tone: 'info',
                    priority: 'medium',
                    href: $this->routeIfExists('backoffice.inspections.index'),
                    cta: 'Abrir vistorias',
                ),
            ],

            'support_agent' => [
                $this->panel(
                    key: 'candidate_support',
                    title: 'Atendimento',
                    description: 'Tickets, visitas, contactos pendentes e FAQ operacional.',
                    icon: 'support',
                    value: null,
                    meta: 'Apoio ao candidato',
                    tone: 'primary',
                    priority: 'medium',
                    href: $this->routeIfExists('backoffice.support.index'),
                    cta: 'Abrir atendimento',
                ),
            ],

            'auditor' => [
                $this->panel(
                    key: 'audit_readonly',
                    title: 'Auditoria em leitura',
                    description: 'Eventos, acessos sensíveis, RGPD e relatórios autorizados.',
                    icon: 'security',
                    value: null,
                    meta: 'Modo consulta',
                    tone: 'neutral',
                    priority: 'low',
                    href: $this->routeIfExists('backoffice.audit.index'),
                    cta: 'Abrir auditoria',
                ),
            ],

            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function panel(
        string $key,
        string $title,
        string $description,
        string $icon = 'dashboard',
        int|string|null $value = null,
        ?string $meta = null,
        string $tone = 'neutral',
        string $priority = 'none',
        ?string $href = null,
        string $cta = 'Abrir',
        array $badges = [],
    ): array {
        return [
            'key' => $key,
            'id' => $key,
            'title' => $title,
            'description' => $description,
            'icon' => $icon,
            'value' => $value,
            'meta' => $meta,
            'tone' => $tone,
            'priority' => $priority,
            'href' => $href,
            'cta' => $cta,
            'badges' => $badges,
        ];
    }

    private function routeIfExists(string $name): ?string
    {
        return Route::has($name) ? route($name) : null;
    }
}
