<?php

namespace App\Services\Cases;

use App\Data\Cases\CaseActionData;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\DataSubjectRequest;
use App\Models\DocumentSubmission;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CaseNextActionResolver
{
    public function __construct(
        private readonly CaseAuthorizationService $authorization,
        private readonly CaseTypeRegistry $registry,
    ) {}

    public function forCase(User $user, string $caseType, Model $case): CaseActionData
    {
        if (! $this->authorization->canMutateCases($user)) {
            return new CaseActionData(
                label: 'Acompanhar processo',
                description: 'Perfil de consulta sem ações mutáveis neste espaço de trabalho.',
            );
        }

        return match ($caseType) {
            'contest' => $this->action($user, 'Acompanhar concurso', 'Rever calendário, candidaturas, listas e tarefas do concurso.', $caseType, $case),
            'contract' => $this->contract($user, $case),
            'maintenance_request' => $this->maintenance($user, $case),
            'inspection' => $this->inspection($user, $case),
            'complaint' => $this->complaint($user, $case),
            'support_ticket' => $this->ticket($user, $case),
            'housing_unit' => $this->action($user, 'Acompanhar fogo', 'Consultar contratos, manutenção e vistorias associadas ao imóvel.', $caseType, $case),
            'document_case' => $this->document($user, $case),
            'rgpd_request' => $this->rgpd($user, $case),
            'audit_case' => new CaseActionData('Consultar auditoria', 'Evento imutável e apenas consultivo.', enabled: false),
            default => $this->action($user, 'Acompanhar processo', 'Consultar informação agregada autorizada.', $caseType, $case),
        };
    }

    private function contract(User $user, Model $case): CaseActionData
    {
        if (! $case instanceof Contract) {
            return $this->fallback();
        }

        if ($case->documents()->doesntExist() && $case->generatedDocuments()->doesntExist()) {
            return $this->action($user, 'Rever documentação contratual', 'Não existem documentos contratuais associados neste processo.', 'contract', $case);
        }

        return $this->action($user, 'Acompanhar contrato', 'Consultar estado, documentos, vistorias e tarefas associadas.', 'contract', $case);
    }

    private function maintenance(User $user, Model $case): CaseActionData
    {
        if (! $case instanceof MaintenanceRequest) {
            return $this->fallback();
        }

        if ($case->assignments()->doesntExist()) {
            return $this->action($user, 'Atribuir responsável', 'Pedido sem responsável operacional atribuído.', 'maintenance_request', $case);
        }

        return $this->action($user, 'Acompanhar manutenção', 'Consultar intervenções, anexos e tarefas do pedido.', 'maintenance_request', $case);
    }

    private function inspection(User $user, Model $case): CaseActionData
    {
        if (! $case instanceof PropertyInspection) {
            return $this->fallback();
        }

        if ($case->scheduled_for === null) {
            return $this->action($user, 'Agendar vistoria', 'A vistoria ainda não tem data registada.', 'inspection', $case);
        }

        return $this->action($user, 'Acompanhar vistoria', 'Consultar checklist, evidências e relatório técnico.', 'inspection', $case);
    }

    private function complaint(User $user, Model $case): CaseActionData
    {
        if (! $case instanceof Complaint) {
            return $this->fallback();
        }

        if ($case->reviews()->doesntExist()) {
            return $this->action($user, 'Analisar reclamação', 'A reclamação ainda não tem revisão registada.', 'complaint', $case);
        }

        return $this->action($user, 'Acompanhar reclamação', 'Consultar revisão, pedidos complementares e decisão.', 'complaint', $case);
    }

    private function ticket(User $user, Model $case): CaseActionData
    {
        if (! $case instanceof SupportTicket) {
            return $this->fallback();
        }

        if ($case->assigned_to === null) {
            return $this->action($user, 'Atribuir pedido de apoio', 'Pedido sem responsável operacional atribuído.', 'support_ticket', $case);
        }

        return $this->action($user, 'Acompanhar pedido de apoio', 'Consultar mensagens, anexos e tarefas associadas.', 'support_ticket', $case);
    }

    private function document(User $user, Model $case): CaseActionData
    {
        if (! $case instanceof DocumentSubmission) {
            return $this->fallback();
        }

        if ($case->reviewed_at === null && $case->validated_at === null) {
            return $this->action($user, 'Rever documento', 'Documento sem revisão/validação formal registada.', 'document_case', $case);
        }

        return $this->action($user, 'Acompanhar documento', 'Consultar estado documental e relações processuais.', 'document_case', $case);
    }

    private function rgpd(User $user, Model $case): CaseActionData
    {
        if (! $case instanceof DataSubjectRequest) {
            return $this->fallback();
        }

        if ($case->assigned_to === null) {
            return $this->action($user, 'Atribuir pedido RGPD', 'Pedido de titular sem responsável atribuído.', 'rgpd_request', $case);
        }

        return $this->action($user, 'Acompanhar pedido RGPD', 'Consultar ações, prazos e exportações autorizadas.', 'rgpd_request', $case);
    }

    private function action(User $user, string $label, string $description, string $caseType, Model $case): CaseActionData
    {
        $config = $this->registry->get($caseType);
        $route = $config['legacy_route'] ?? null;
        $permission = $config['permission'] ?? null;
        $enabled = is_string($route)
            && is_string($permission)
            && $this->authorization->canSeeItem($user, ['route' => $route, 'permission' => $permission]);

        return new CaseActionData(
            label: $label,
            description: $description,
            route: $enabled ? $route : null,
            parameters: $enabled ? [$case] : [],
            enabled: $enabled,
            tone: 'civic',
        );
    }

    private function fallback(): CaseActionData
    {
        return new CaseActionData('Acompanhar processo', 'Consultar informação autorizada do caso.', enabled: false);
    }
}
