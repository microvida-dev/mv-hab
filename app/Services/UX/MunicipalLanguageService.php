<?php

namespace App\Services\UX;

class MunicipalLanguageService
{
    public function __construct(private readonly TerminologyService $terminology) {}

    public function term(string $term): string
    {
        return $this->terminology->translate($term);
    }

    public function roleLabel(string $role): string
    {
        return match ($role) {
            'administrator' => 'Administrador',
            'municipal_technician' => 'Técnico Municipal',
            'jury' => 'Júri',
            'legal_manager' => 'Gestor Jurídico',
            'financial_manager' => 'Gestor Financeiro',
            'housing_manager' => 'Gestor de Habitação',
            'maintenance_manager' => 'Gestor de Manutenção',
            'inspection_manager' => 'Gestor de Vistorias',
            'support_agent' => 'Técnico de Atendimento',
            'auditor' => 'Auditor',
            'candidate' => 'Candidato',
            default => str($role)->replace('_', ' ')->title()->toString(),
        };
    }

    public function priorityLabel(string $priority): string
    {
        return match ($priority) {
            'urgent' => 'Urgente',
            'critical' => 'Crítica',
            'high' => 'Alta',
            'low' => 'Baixa',
            default => 'Normal',
        };
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Rascunho',
            'pending' => 'Pendente',
            'ready' => 'Preparado',
            'review', 'in_review' => 'Em revisão',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
            'completed' => 'Concluído',
            'blocked' => 'Bloqueado',
            'overdue' => 'Em atraso',
            'cancelled' => 'Cancelado',
            default => str($status)->replace('_', ' ')->title()->toString(),
        };
    }
}
