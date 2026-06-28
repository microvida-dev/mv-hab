<?php

namespace App\Services\UX;

class TerminologyService
{
    /**
     * @return array<string, string>
     */
    public function replacements(): array
    {
        return [
            'Dashboard' => 'Painel Principal',
            'Workspace' => 'Espaço de Trabalho',
            'Workspaces' => 'Espaços de Trabalho',
            'Search' => 'Pesquisa',
            'Command Center' => 'Centro de Comandos',
            'Smart Action Center' => 'Centro de Trabalho',
            'My Work' => 'O Meu Trabalho',
            'Inbox' => 'Caixa de Entrada',
            'Timeline' => 'Cronologia',
            'Status' => 'Estado',
            'Submit' => 'Submeter',
            'Save' => 'Guardar',
            'Edit' => 'Editar',
            'Delete' => 'Eliminar',
            'Cancel' => 'Cancelar',
            'Pending' => 'Pendente',
            'Review' => 'Revisão',
            'Draft' => 'Rascunho',
            'Ready' => 'Preparado',
            'Tasks' => 'Tarefas',
            'Reports' => 'Relatórios',
            'Settings' => 'Configurações',
            'Notifications' => 'Notificações',
            'Candidate' => 'Candidato',
            'Support' => 'Apoio',
            'Insights' => 'Indicadores',
            'Work Task' => 'Tarefa',
            'Work Tasks' => 'Tarefas',
        ];
    }

    public function translate(string $term): string
    {
        return $this->replacements()[$term] ?? $term;
    }

    /**
     * @return list<string>
     */
    public function criticalEnglishTerms(): array
    {
        return array_keys($this->replacements());
    }

    /**
     * @return list<string>
     */
    public function preservedTechnicalTerms(): array
    {
        return [
            'workspace',
            'work_task',
            'dashboard',
            'route',
            'middleware',
            'policy',
            'permission',
            'role',
            'slug',
            'uuid',
            'SLA',
            'KPI',
            'RBAC',
            'RGPD',
        ];
    }

    public function shouldPreserve(string $term): bool
    {
        return in_array($term, $this->preservedTechnicalTerms(), true);
    }
}
