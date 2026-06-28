<?php

namespace App\Services\Cases;

use App\Data\Cases\CaseChecklistItemData;
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

class CaseChecklistAggregator
{
    /**
     * @return list<CaseChecklistItemData>
     */
    public function forCase(string $caseType, Model $case): array
    {
        return match ($caseType) {
            'contest' => $this->contest($case),
            'contract' => $this->contract($case),
            'maintenance_request' => $this->maintenance($case),
            'inspection' => $this->inspection($case),
            'complaint' => $this->complaint($case),
            'support_ticket' => $this->ticket($case),
            'housing_unit' => $this->housingUnit($case),
            'document_case' => $this->document($case),
            'rgpd_request' => $this->rgpd($case),
            'audit_case' => $this->audit($case),
            default => [$this->item('Resumo processual', 'completed', 'Caso preparado para consulta.')],
        };
    }

    /**
     * @return list<CaseChecklistItemData>
     */
    private function contest(Model $case): array
    {
        if (! $case instanceof Contest) {
            return [];
        }

        return [
            $this->item('Programa definido', $case->program()->exists() ? 'completed' : 'pending', 'Associação ao programa municipal.'),
            $this->item('Prazos definidos', $case->opens_at !== null && $case->closes_at !== null ? 'completed' : 'warning', 'Calendário de abertura e fecho.'),
            $this->item('Candidaturas recebidas', $case->applications()->exists() ? 'completed' : 'pending', 'Candidaturas associadas ao concurso.'),
            $this->item('Critérios de elegibilidade', $case->eligibilityRuleSets()->exists() ? 'completed' : 'pending', 'Regras de elegibilidade existentes.'),
            $this->item('Critérios de pontuação', $case->scoringRuleSets()->exists() ? 'completed' : 'pending', 'Regras de pontuação existentes.'),
            $this->item('Lista provisória', $case->provisionalLists()->exists() ? 'completed' : 'pending', 'Publicação provisória quando aplicável.'),
            $this->item('Lista definitiva', $case->definitiveLists()->exists() ? 'completed' : 'pending', 'Publicação definitiva quando aplicável.'),
        ];
    }

    /**
     * @return list<CaseChecklistItemData>
     */
    private function contract(Model $case): array
    {
        if (! $case instanceof Contract) {
            return [];
        }

        return [
            $this->item('Fogo associado', $case->housingUnit()->exists() ? 'completed' : 'warning', 'Contrato ligado a fogo municipal.'),
            $this->item('Candidatura associada', $case->application()->exists() ? 'completed' : 'not_applicable', 'Origem processual do contrato.'),
            $this->item('Datas contratuais', $case->start_date !== null ? 'completed' : 'pending', 'Data de início definida.'),
            $this->item('Documentos', $case->documents()->exists() || $case->generatedDocuments()->exists() ? 'completed' : 'pending', 'Documentos contratuais registados.'),
            $this->item('Validações', $case->validations()->exists() ? 'completed' : 'pending', 'Validações administrativas registadas.'),
            $this->item('Vistorias', $case->propertyInspections()->exists() ? 'completed' : 'not_applicable', 'Vistorias associadas quando aplicável.'),
        ];
    }

    /**
     * @return list<CaseChecklistItemData>
     */
    private function maintenance(Model $case): array
    {
        if (! $case instanceof MaintenanceRequest) {
            return [];
        }

        return [
            $this->item('Pedido classificado', $this->hasValue($case, 'priority') && $this->hasValue($case, 'urgency') ? 'completed' : 'pending', 'Prioridade e urgência registadas.'),
            $this->item('Imóvel associado', $case->housingUnit()->exists() ? 'completed' : 'warning', 'Fogo/imóvel relacionado.'),
            $this->item('Responsável atribuído', $case->assignments()->exists() ? 'completed' : 'pending', 'Atribuição operacional registada.'),
            $this->item('Intervenção', $case->interventions()->exists() ? 'completed' : 'pending', 'Intervenções técnicas associadas.'),
            $this->item('Anexos', $case->attachments()->exists() ? 'completed' : 'not_applicable', 'Anexos protegidos por autorização.'),
            $this->item('Conclusão', $case->resolved_at !== null ? 'completed' : 'pending', 'Fecho técnico ou resolução registada.'),
        ];
    }

    /**
     * @return list<CaseChecklistItemData>
     */
    private function inspection(Model $case): array
    {
        if (! $case instanceof PropertyInspection) {
            return [];
        }

        return [
            $this->item('Imóvel associado', $case->housingUnit()->exists() ? 'completed' : 'warning', 'Fogo/imóvel relacionado.'),
            $this->item('Agendamento', $case->scheduled_for !== null ? 'completed' : 'pending', 'Data de vistoria definida.'),
            $this->item('Checklist técnica', $case->items()->exists() ? 'completed' : 'pending', 'Itens técnicos registados.'),
            $this->item('Evidências', $case->attachments()->exists() ? 'completed' : 'not_applicable', 'Fotografias/anexos autorizados.'),
            $this->item('Relatório', $case->report()->exists() ? 'completed' : 'pending', 'Relatório técnico associado.'),
            $this->item('Validação', $case->validated_at !== null ? 'completed' : 'pending', 'Validação municipal registada.'),
        ];
    }

    /**
     * @return list<CaseChecklistItemData>
     */
    private function complaint(Model $case): array
    {
        if (! $case instanceof Complaint) {
            return [];
        }

        return [
            $this->item('Processo associado', $case->application()->exists() ? 'completed' : 'warning', 'Candidatura relacionada.'),
            $this->item('Receção', $case->received_at !== null ? 'completed' : 'pending', 'Receção municipal registada.'),
            $this->item('Responsável', $case->assigned_to !== null ? 'completed' : 'pending', 'Responsável de análise atribuído.'),
            $this->item('Revisões', $case->reviews()->exists() ? 'completed' : 'pending', 'Análise/revisão administrativa.'),
            $this->item('Decisão', $case->decision()->exists() ? 'completed' : 'pending', 'Decisão formal quando aplicável.'),
            $this->item('Encerramento', $case->closed_at !== null ? 'completed' : 'pending', 'Fecho do processo.'),
        ];
    }

    /**
     * @return list<CaseChecklistItemData>
     */
    private function ticket(Model $case): array
    {
        if (! $case instanceof SupportTicket) {
            return [];
        }

        return [
            $this->item('Categoria', $this->hasValue($case, 'category') ? 'completed' : 'pending', 'Categoria operacional definida.'),
            $this->item('Prioridade', $this->hasValue($case, 'priority') ? 'completed' : 'pending', 'Prioridade de atendimento definida.'),
            $this->item('Responsável', $case->assigned_to !== null ? 'completed' : 'pending', 'Atribuição a técnico/equipa.'),
            $this->item('Mensagens', $case->messages()->exists() ? 'completed' : 'pending', 'Interações autorizadas.'),
            $this->item('Anexos', $case->attachments()->exists() ? 'completed' : 'not_applicable', 'Anexos privados protegidos.'),
            $this->item('Resolução', $case->resolved_at !== null || $case->closed_at !== null ? 'completed' : 'pending', 'Tratamento concluído quando aplicável.'),
        ];
    }

    /**
     * @return list<CaseChecklistItemData>
     */
    private function housingUnit(Model $case): array
    {
        if (! $case instanceof HousingUnit) {
            return [];
        }

        return [
            $this->item('Identificação do fogo', $this->hasValue($case, 'code') ? 'completed' : 'warning', 'Código interno do imóvel.'),
            $this->item('Tipologia', $this->hasValue($case, 'typology') ? 'completed' : 'pending', 'Tipologia registada.'),
            $this->item('Ficha pública', $case->is_public ? 'completed' : 'not_applicable', 'Publicação pública quando aprovada.'),
            $this->item('Contratos', $case->contracts()->exists() ? 'completed' : 'not_applicable', 'Contratos associados.'),
            $this->item('Concursos', $case->contestHousingUnits()->exists() ? 'completed' : 'not_applicable', 'Associação a concursos.'),
            $this->item('Manutenção', $case->maintenanceRequests()->exists() ? 'warning' : 'not_applicable', 'Pedidos técnicos associados.'),
        ];
    }

    /**
     * @return list<CaseChecklistItemData>
     */
    private function document(Model $case): array
    {
        if (! $case instanceof DocumentSubmission) {
            return [];
        }

        return [
            $this->item('Tipo documental', $case->documentType()->exists() ? 'completed' : 'pending', 'Tipo documental associado.'),
            $this->item('Submissão', $case->submitted_at !== null ? 'completed' : 'pending', 'Data de submissão registada.'),
            $this->item('Revisão', $case->reviews()->exists() || $case->reviewed_at !== null ? 'completed' : 'pending', 'Revisão técnica ou documental.'),
            $this->item('Validação', $case->validated_at !== null ? 'completed' : 'pending', 'Validação formal quando aplicável.'),
            $this->item('IA documental', $case->documentAiAnalyses()->exists() ? 'completed' : 'not_applicable', 'Análise assistiva sem decisão automática.'),
        ];
    }

    /**
     * @return list<CaseChecklistItemData>
     */
    private function rgpd(Model $case): array
    {
        if (! $case instanceof DataSubjectRequest) {
            return [];
        }

        return [
            $this->item('Pedido recebido', $case->received_at !== null ? 'completed' : 'pending', 'Receção do pedido do titular.'),
            $this->item('Identidade verificada', $case->identity_verified_at !== null ? 'completed' : 'pending', 'Verificação de identidade quando aplicável.'),
            $this->item('Responsável', $case->assigned_to !== null ? 'completed' : 'pending', 'Responsável municipal atribuído.'),
            $this->item('Ações', $case->actions()->exists() ? 'completed' : 'pending', 'Ações RGPD registadas.'),
            $this->item('Exportação/anonimização', $case->exports()->exists() || $case->anonymizationRequests()->exists() ? 'completed' : 'not_applicable', 'Operações protegidas e auditáveis.'),
            $this->item('Conclusão', $case->completed_at !== null ? 'completed' : 'pending', 'Conclusão dentro do fluxo RGPD.'),
        ];
    }

    /**
     * @return list<CaseChecklistItemData>
     */
    private function audit(Model $case): array
    {
        if (! $case instanceof AuditEvent) {
            return [];
        }

        return [
            $this->item('Evento imutável', 'completed', 'Evento de auditoria apenas consultivo.'),
            $this->item('Categoria', $this->hasValue($case, 'event_category') ? 'completed' : 'pending', 'Categoria do evento registada.'),
            $this->item('Severidade', $this->hasValue($case, 'severity') ? 'completed' : 'pending', 'Severidade operacional registada.'),
            $this->item('Relações', $case->auditable_type !== null || $case->related_type !== null ? 'completed' : 'not_applicable', 'Recurso relacionado quando autorizado.'),
        ];
    }

    private function item(string $label, string $status, string $description): CaseChecklistItemData
    {
        return new CaseChecklistItemData($label, $status, $description);
    }

    private function hasValue(Model $case, string $attribute): bool
    {
        $value = $case->getAttribute($attribute);

        return $value !== null && $value !== '';
    }
}
