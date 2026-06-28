<?php

namespace App\Services\Cases;

use App\Data\Cases\CaseRelationData;
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
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CaseRelationsService
{
    public function __construct(private readonly CaseAuthorizationService $authorization) {}

    /**
     * @return list<CaseRelationData>
     */
    public function forCase(User $user, string $caseType, Model $case): array
    {
        return match ($caseType) {
            'contest' => $this->contest($user, $case),
            'contract' => $this->contract($user, $case),
            'maintenance_request' => $this->maintenance($user, $case),
            'inspection' => $this->inspection($user, $case),
            'complaint' => $this->complaint($user, $case),
            'support_ticket' => $this->ticket($user, $case),
            'housing_unit' => $this->housingUnit($user, $case),
            'document_case' => $this->document($user, $case),
            'rgpd_request' => $this->rgpd($user, $case),
            'audit_case' => $this->audit($user, $case),
            default => [],
        };
    }

    /**
     * @return list<CaseRelationData>
     */
    private function contest(User $user, Model $case): array
    {
        if (! $case instanceof Contest) {
            return [];
        }

        return array_values(array_filter([
            $this->relation($user, 'Programa', 'program', 'Programa associado', 'admin.programs.show', 'programs.view', $case->program),
            $this->countRelation($user, 'Candidaturas', 'application', $case->applications()->count(), 'Processos associados ao concurso.', 'applications.view'),
            $this->countRelation($user, 'Fogos associados', 'housing_unit', $case->contestHousingUnits()->count(), 'Oferta habitacional do concurso.', 'housing_units.view'),
            $this->countRelation($user, 'Listas provisórias', 'list', $case->provisionalLists()->count(), 'Listas provisórias associadas.', 'public_lists.view'),
            $this->countRelation($user, 'Listas definitivas', 'list', $case->definitiveLists()->count(), 'Listas definitivas associadas.', 'public_lists.view'),
        ]));
    }

    /**
     * @return list<CaseRelationData>
     */
    private function contract(User $user, Model $case): array
    {
        if (! $case instanceof Contract) {
            return [];
        }

        return array_values(array_filter([
            $this->relation($user, 'Candidatura', 'application', 'Candidatura de origem.', 'backoffice.cases.applications.show', 'applications.view', $case->application),
            $this->relation($user, 'Fogo', 'housing_unit', 'Fogo municipal associado.', 'backoffice.cases.housing-units.show', 'housing_units.view', $case->housingUnit),
            $this->relation($user, 'Concurso', 'contest', 'Concurso de origem.', 'backoffice.cases.contests.show', 'contests.view', $case->contest),
            $this->countRelation($user, 'Registos financeiros', 'finance', $case->payments()->count(), 'Registos de pagamento existentes no módulo financeiro autorizado.', 'finance.view'),
            $this->countRelation($user, 'Vistorias', 'inspection', $case->propertyInspections()->count(), 'Vistorias associadas ao contrato.', 'inspections.view'),
            $this->countRelation($user, 'Manutenção', 'maintenance_request', $case->maintenanceRequests()->count(), 'Pedidos de manutenção associados.', 'maintenance_requests.view'),
        ]));
    }

    /**
     * @return list<CaseRelationData>
     */
    private function maintenance(User $user, Model $case): array
    {
        if (! $case instanceof MaintenanceRequest) {
            return [];
        }

        return array_values(array_filter([
            $this->relation($user, 'Fogo', 'housing_unit', 'Fogo/imóvel associado.', 'backoffice.cases.housing-units.show', 'housing_units.view', $case->housingUnit),
            $this->relation($user, 'Contrato', 'contract', 'Contrato relacionado.', 'backoffice.cases.contracts.show', 'contracts.view', $case->leaseContract),
            $this->relation($user, 'Candidatura', 'application', 'Candidatura relacionada.', 'backoffice.cases.applications.show', 'applications.view', $case->application),
            $this->countRelation($user, 'Intervenções', 'maintenance_intervention', $case->interventions()->count(), 'Intervenções técnicas registadas.', 'maintenance_requests.view'),
        ]));
    }

    /**
     * @return list<CaseRelationData>
     */
    private function inspection(User $user, Model $case): array
    {
        if (! $case instanceof PropertyInspection) {
            return [];
        }

        return array_values(array_filter([
            $this->relation($user, 'Fogo', 'housing_unit', 'Fogo/imóvel associado.', 'backoffice.cases.housing-units.show', 'housing_units.view', $case->housingUnit),
            $this->relation($user, 'Contrato', 'contract', 'Contrato relacionado.', 'backoffice.cases.contracts.show', 'contracts.view', $case->leaseContract),
            $this->relation($user, 'Candidatura', 'application', 'Candidatura relacionada.', 'backoffice.cases.applications.show', 'applications.view', $case->application),
            $this->countRelation($user, 'Itens de checklist', 'inspection_item', $case->items()->count(), 'Itens técnicos registados.', 'inspections.view'),
        ]));
    }

    /**
     * @return list<CaseRelationData>
     */
    private function complaint(User $user, Model $case): array
    {
        if (! $case instanceof Complaint) {
            return [];
        }

        return array_values(array_filter([
            $this->relation($user, 'Candidatura', 'application', 'Candidatura associada.', 'backoffice.cases.applications.show', 'applications.view', $case->application),
            $this->countRelation($user, 'Revisões', 'complaint_review', $case->reviews()->count(), 'Revisões de reclamação.', 'complaints.view'),
            $this->countRelation($user, 'Pedidos complementares', 'additional_information', $case->additionalInformationRequests()->count(), 'Pedidos de informação adicional.', 'complaints.view'),
        ]));
    }

    /**
     * @return list<CaseRelationData>
     */
    private function ticket(User $user, Model $case): array
    {
        if (! $case instanceof SupportTicket) {
            return [];
        }

        return array_values(array_filter([
            $this->relation($user, 'Candidatura', 'application', 'Candidatura associada.', 'backoffice.cases.applications.show', 'applications.view', $case->application),
            $this->relation($user, 'Concurso', 'contest', 'Concurso associado.', 'backoffice.cases.contests.show', 'contests.view', $case->contest),
            $this->relation($user, 'Fogo', 'housing_unit', 'Fogo associado.', 'backoffice.cases.housing-units.show', 'housing_units.view', $case->housingUnit),
            $this->countRelation($user, 'Mensagens', 'support_message', $case->messages()->count(), 'Mensagens autorizadas do pedido.', 'support.view'),
        ]));
    }

    /**
     * @return list<CaseRelationData>
     */
    private function housingUnit(User $user, Model $case): array
    {
        if (! $case instanceof HousingUnit) {
            return [];
        }

        return array_values(array_filter([
            $this->countRelation($user, 'Contratos', 'contract', $case->contracts()->count(), 'Contratos associados ao fogo.', 'contracts.view'),
            $this->countRelation($user, 'Concursos', 'contest', $case->contestHousingUnits()->count(), 'Associações a concursos.', 'contests.view'),
            $this->countRelation($user, 'Manutenção', 'maintenance_request', $case->maintenanceRequests()->count(), 'Pedidos de manutenção do imóvel.', 'maintenance_requests.view'),
            $this->countRelation($user, 'Vistorias', 'inspection', $case->propertyInspections()->count(), 'Vistorias técnicas do imóvel.', 'inspections.view'),
            $this->countRelation($user, 'Visitas', 'visit', $case->housingVisits()->count(), 'Visitas agendadas ao fogo.', 'visits.view'),
        ]));
    }

    /**
     * @return list<CaseRelationData>
     */
    private function document(User $user, Model $case): array
    {
        if (! $case instanceof DocumentSubmission) {
            return [];
        }

        return array_values(array_filter([
            $this->relation($user, 'Candidatura', 'application', 'Candidatura associada.', 'backoffice.cases.applications.show', 'applications.view', $case->application),
            $this->relation($user, 'Contrato', 'contract', 'Contrato associado.', 'backoffice.cases.contracts.show', 'contracts.view', $case->contract),
            $this->countRelation($user, 'Versões', 'document_version', $case->versions()->count(), 'Versões documentais privadas.', 'documents.view'),
            $this->countRelation($user, 'Revisões', 'document_review', $case->reviews()->count(), 'Revisões documentais.', 'documents.view'),
        ]));
    }

    /**
     * @return list<CaseRelationData>
     */
    private function rgpd(User $user, Model $case): array
    {
        if (! $case instanceof DataSubjectRequest) {
            return [];
        }

        return array_values(array_filter([
            $this->countRelation($user, 'Ações RGPD', 'rgpd_action', $case->actions()->count(), 'Ações do pedido de titular.', 'privacy.view'),
            $this->countRelation($user, 'Exportações', 'data_export', $case->exports()->count(), 'Exportações protegidas e auditadas.', 'privacy.view'),
            $this->countRelation($user, 'Anonimizações', 'anonymization', $case->anonymizationRequests()->count(), 'Pedidos de anonimização associados.', 'privacy.view'),
        ]));
    }

    /**
     * @return list<CaseRelationData>
     */
    private function audit(User $user, Model $case): array
    {
        if (! $case instanceof AuditEvent) {
            return [];
        }

        return array_values(array_filter([
            $this->genericRelation($user, 'Recurso auditado', 'audit_related', $case->auditable_type !== null ? 'Existe recurso auditado associado.' : 'Sem recurso auditado associado.', 'audit_logs.view'),
            $this->genericRelation($user, 'Recurso relacionado', 'audit_related', $case->related_type !== null ? 'Existe recurso relacionado associado.' : 'Sem recurso relacionado associado.', 'audit_logs.view'),
        ]));
    }

    private function relation(User $user, string $label, string $type, string $description, string $route, string $permission, ?Model $model): ?CaseRelationData
    {
        if (! $model instanceof Model || ! $this->authorization->canSeeItem($user, ['route' => $route, 'permission' => $permission])) {
            return null;
        }

        return new CaseRelationData($label, $type, $description, $route, [$model], 'completed');
    }

    private function countRelation(User $user, string $label, string $type, int $count, string $description, string $permission): ?CaseRelationData
    {
        if ($count <= 0 || ! $this->authorization->hasPermission($user, $permission)) {
            return null;
        }

        return new CaseRelationData($label.' ('.$count.')', $type, $description, null, [], 'completed');
    }

    private function genericRelation(User $user, string $label, string $type, string $description, string $permission): ?CaseRelationData
    {
        if (! $this->authorization->hasPermission($user, $permission)) {
            return null;
        }

        return new CaseRelationData($label, $type, $description, null, [], 'neutral');
    }
}
