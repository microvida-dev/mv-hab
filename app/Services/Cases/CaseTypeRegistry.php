<?php

namespace App\Services\Cases;

use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\Complaint;
use App\Models\Contest;
use App\Models\Contract;
use App\Models\DataSubjectRequest;
use App\Models\DocumentSubmission;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Model;

class CaseTypeRegistry
{
    /**
     * @return array<string, array{
     *     label: string,
     *     plural: string,
     *     model: class-string<Model>,
     *     permission: string,
     *     route: string,
     *     legacy_route: string|null,
     *     workspace: string,
     *     tabs: list<array{key: string, label: string, permission: string}>
     * }>
     */
    public function types(): array
    {
        return [
            'application' => $this->type('Candidatura', 'Candidaturas', Application::class, 'applications.view', 'backoffice.cases.applications.show', 'backoffice.applications.show', 'atendimento', [
                $this->tab('summary', 'Resumo', 'applications.view'),
                $this->tab('timeline', 'Cronologia', 'applications.view'),
                $this->tab('documents', 'Documentos', 'documents.view'),
                $this->tab('tasks', 'Tarefas', 'work_tasks.view'),
                $this->tab('relations', 'Relações', 'applications.view'),
                $this->tab('history', 'Histórico', 'audit_logs.view'),
            ]),
            'contest' => $this->type('Concurso', 'Concursos', Contest::class, 'contests.view', 'backoffice.cases.contests.show', 'admin.contests.show', 'concursos', [
                $this->tab('summary', 'Resumo', 'contests.view'),
                $this->tab('timeline', 'Cronologia', 'contests.view'),
                $this->tab('checklist', 'Checklist', 'contests.view'),
                $this->tab('relations', 'Candidaturas e listas', 'applications.view'),
                $this->tab('documents', 'Documentos e minutas', 'documents.view'),
                $this->tab('tasks', 'Tarefas', 'work_tasks.view'),
                $this->tab('history', 'Auditoria', 'audit_logs.view'),
            ]),
            'contract' => $this->type('Contrato', 'Contratos', Contract::class, 'contracts.view', 'backoffice.cases.contracts.show', 'backoffice.contracts.leases.show', 'patrimonio', [
                $this->tab('summary', 'Resumo', 'contracts.view'),
                $this->tab('timeline', 'Cronologia', 'contracts.view'),
                $this->tab('checklist', 'Checklist', 'contracts.view'),
                $this->tab('documents', 'Documentos', 'documents.view'),
                $this->tab('relations', 'Fogo, renda e vistorias', 'contracts.view'),
                $this->tab('tasks', 'Tarefas', 'work_tasks.view'),
                $this->tab('history', 'Auditoria', 'audit_logs.view'),
            ]),
            'maintenance_request' => $this->type('Pedido de manutenção', 'Manutenção', MaintenanceRequest::class, 'maintenance_requests.view', 'backoffice.cases.maintenance.show', 'backoffice.maintenance.requests.show', 'patrimonio', [
                $this->tab('summary', 'Resumo', 'maintenance_requests.view'),
                $this->tab('timeline', 'Cronologia', 'maintenance_requests.view'),
                $this->tab('checklist', 'Checklist', 'maintenance_requests.view'),
                $this->tab('documents', 'Anexos', 'maintenance_requests.view'),
                $this->tab('relations', 'Imóvel e intervenções', 'maintenance_requests.view'),
                $this->tab('tasks', 'Tarefas', 'work_tasks.view'),
                $this->tab('history', 'Auditoria', 'audit_logs.view'),
            ]),
            'inspection' => $this->type('Vistoria', 'Vistorias', PropertyInspection::class, 'inspections.view', 'backoffice.cases.inspections.show', 'backoffice.inspections.show', 'patrimonio', [
                $this->tab('summary', 'Resumo', 'inspections.view'),
                $this->tab('timeline', 'Cronologia', 'inspections.view'),
                $this->tab('checklist', 'Checklist', 'inspections.view'),
                $this->tab('documents', 'Evidências', 'inspections.view'),
                $this->tab('relations', 'Imóvel e contrato', 'inspections.view'),
                $this->tab('tasks', 'Tarefas', 'work_tasks.view'),
                $this->tab('history', 'Auditoria', 'audit_logs.view'),
            ]),
            'complaint' => $this->type('Reclamação', 'Reclamações', Complaint::class, 'complaints.view', 'backoffice.cases.complaints.show', 'backoffice.complaints.show', 'atendimento', [
                $this->tab('summary', 'Resumo', 'complaints.view'),
                $this->tab('timeline', 'Cronologia', 'complaints.view'),
                $this->tab('checklist', 'Checklist', 'complaints.view'),
                $this->tab('documents', 'Documentos', 'documents.view'),
                $this->tab('communications', 'Comunicações', 'complaints.view'),
                $this->tab('tasks', 'Tarefas', 'work_tasks.view'),
                $this->tab('history', 'Auditoria', 'audit_logs.view'),
            ]),
            'support_ticket' => $this->type('Pedido de apoio', 'Apoio', SupportTicket::class, 'support.view', 'backoffice.cases.tickets.show', 'backoffice.support-tickets.show', 'atendimento', [
                $this->tab('summary', 'Resumo', 'support.view'),
                $this->tab('timeline', 'Cronologia', 'support.view'),
                $this->tab('checklist', 'Checklist', 'support.view'),
                $this->tab('communications', 'Mensagens', 'support.view'),
                $this->tab('documents', 'Anexos', 'support.view'),
                $this->tab('tasks', 'Tarefas', 'work_tasks.view'),
                $this->tab('history', 'Auditoria', 'audit_logs.view'),
            ]),
            'housing_unit' => $this->type('Fogo', 'Fogos', HousingUnit::class, 'housing_units.view', 'backoffice.cases.housing-units.show', 'housing-units.show', 'patrimonio', [
                $this->tab('summary', 'Resumo', 'housing_units.view'),
                $this->tab('timeline', 'Cronologia', 'housing_units.view'),
                $this->tab('checklist', 'Checklist', 'housing_units.view'),
                $this->tab('documents', 'Documentos', 'documents.view'),
                $this->tab('relations', 'Contratos, concursos e manutenção', 'housing_units.view'),
                $this->tab('tasks', 'Tarefas', 'work_tasks.view'),
                $this->tab('history', 'Auditoria', 'audit_logs.view'),
            ]),
            'document_case' => $this->type('Documento', 'Documentos', DocumentSubmission::class, 'documents.view', 'backoffice.cases.documents.show', 'admin.document-reviews.show', 'gestao', [
                $this->tab('summary', 'Resumo', 'documents.view'),
                $this->tab('timeline', 'Cronologia', 'documents.view'),
                $this->tab('checklist', 'Checklist', 'documents.view'),
                $this->tab('relations', 'Processos relacionados', 'documents.view'),
                $this->tab('history', 'Auditoria', 'audit_logs.view'),
            ]),
            'rgpd_request' => $this->type('Pedido RGPD', 'RGPD', DataSubjectRequest::class, 'privacy.view', 'backoffice.cases.rgpd.show', 'backoffice.security.privacy.requests.show', 'gestao', [
                $this->tab('summary', 'Resumo', 'privacy.view'),
                $this->tab('timeline', 'Cronologia', 'privacy.view'),
                $this->tab('checklist', 'Checklist', 'privacy.view'),
                $this->tab('relations', 'Exportações e anonimização', 'privacy.view'),
                $this->tab('history', 'Auditoria', 'audit_logs.view'),
            ]),
            'audit_case' => $this->type('Evento de auditoria', 'Auditoria', AuditEvent::class, 'audit_logs.view', 'backoffice.cases.audit.show', 'backoffice.security.audit.events.show', 'gestao', [
                $this->tab('summary', 'Resumo', 'audit_logs.view'),
                $this->tab('timeline', 'Cronologia', 'audit_logs.view'),
                $this->tab('checklist', 'Checklist', 'audit_logs.view'),
                $this->tab('relations', 'Relações autorizadas', 'audit_logs.view'),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $type): ?array
    {
        return $this->types()[$type] ?? null;
    }

    public function typeFor(Model $case): ?string
    {
        foreach ($this->types() as $type => $config) {
            $modelClass = $config['model'];
            if ($case instanceof $modelClass) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<array{key: string, label: string, permission: string}>  $tabs
     * @return array{
     *     label: string,
     *     plural: string,
     *     model: class-string<Model>,
     *     permission: string,
     *     route: string,
     *     legacy_route: string|null,
     *     workspace: string,
     *     tabs: list<array{key: string, label: string, permission: string}>
     * }
     */
    private function type(string $label, string $plural, string $model, string $permission, string $route, ?string $legacyRoute, string $workspace, array $tabs): array
    {
        return [
            'label' => $label,
            'plural' => $plural,
            'model' => $model,
            'permission' => $permission,
            'route' => $route,
            'legacy_route' => $legacyRoute,
            'workspace' => $workspace,
            'tabs' => $tabs,
        ];
    }

    /**
     * @return array{key: string, label: string, permission: string}
     */
    private function tab(string $key, string $label, string $permission): array
    {
        return compact('key', 'label', 'permission');
    }
}
