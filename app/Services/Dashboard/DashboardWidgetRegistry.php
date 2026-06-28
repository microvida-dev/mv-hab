<?php

namespace App\Services\Dashboard;

use App\Models\User;

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
            $panels = array_merge($panels, $this->panelsForProfile($profile));
        }

        return collect($panels)
            ->unique('key')
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function panelsForProfile(string $profile): array
    {
        return match ($profile) {
            'administrator' => [
                $this->panel('admin_security', 'Administração e segurança', 'Utilizadores, equipas, MFA, alertas e auditoria.'),
                $this->panel('admin_operations', 'Operação transversal', 'Tarefas críticas, relatórios e riscos operacionais.'),
            ],
            'municipal_technician' => [
                $this->panel('technical_review', 'Revisão técnica', 'Candidaturas, documentos, aperfeiçoamentos e SLA.'),
            ],
            'jury' => [
                $this->panel('jury_decision', 'Classificação e listas', 'Processos para pontuação, reclamações e publicações.'),
            ],
            'legal_manager' => [
                $this->panel('legal_review', 'Validação jurídica', 'Contratos, reclamações, audiência prévia e pareceres.'),
            ],
            'financial_manager' => [
                $this->panel('financial_control', 'Controlo financeiro', 'Rendas manuais, pagamentos e contratos com impacto financeiro.'),
            ],
            'housing_manager' => [
                $this->panel('housing_operations', 'Gestão habitacional', 'Fogos, ocupação, contratos operacionais e visitas.'),
            ],
            'maintenance_manager' => [
                $this->panel('maintenance_operations', 'Manutenção', 'Pedidos urgentes, intervenções abertas e tarefas vencidas.'),
            ],
            'inspection_manager' => [
                $this->panel('inspection_operations', 'Vistorias', 'Agenda, autos pendentes e histórico técnico.'),
            ],
            'support_agent' => [
                $this->panel('candidate_support', 'Atendimento', 'Tickets, visitas, contactos pendentes e FAQ operacional.'),
            ],
            'auditor' => [
                $this->panel('audit_readonly', 'Auditoria em leitura', 'Eventos, acessos sensíveis, RGPD e relatórios autorizados.'),
            ],
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    private function panel(string $key, string $title, string $description): array
    {
        return compact('key', 'title', 'description');
    }
}
