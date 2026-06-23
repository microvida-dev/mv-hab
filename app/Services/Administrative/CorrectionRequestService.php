<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequestStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\CorrectionRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CorrectionRequestService
{
    public function __construct(
        private readonly AdministrativeDeadlineService $deadlineService,
        private readonly AdministrativeWorkflowTransitionService $transitionService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(AdministrativeProcess $process, array $data, User $actor): CorrectionRequest
    {
        if ($process->isClosed()) {
            throw ValidationException::withMessages(['process' => 'Processos encerrados não aceitam pedidos de aperfeiçoamento.']);
        }

        if ($this->hasOpenRequest($process)) {
            throw ValidationException::withMessages(['correction_request' => 'Já existe um pedido de aperfeiçoamento aberto neste processo.']);
        }

        return DB::transaction(function () use ($process, $data) {
            $deadline = $data['response_deadline_at'] ?? $this->deadlineService->correctionDeadlineForApplication($this->requiredApplication($process));
            $request = new CorrectionRequest([
                'subject' => $data['subject'],
                'message' => $data['message'],
                'legal_basis' => $data['legal_basis'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'response_deadline_at' => $deadline,
                'candidate_visible' => false,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);
            $request->forceFill([
                'administrative_process_id' => $process->id,
                'application_id' => $process->application_id,
                'user_id' => $process->user_id,
                'request_number' => $this->generateRequestNumber(),
                'status' => CorrectionRequestStatus::Draft,
            ]);
            $request->save();

            foreach ($this->itemsData($data) as $index => $item) {
                $requestItem = $request->items()->make([
                    'issue_type' => $item['issue_type'],
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'required_action' => $item['required_action'],
                    'is_required' => (bool) ($item['is_required'] ?? true),
                    'document_type_id' => $item['document_type_id'] ?? null,
                    'required_document_id' => $item['required_document_id'] ?? null,
                    'sort_order' => $item['sort_order'] ?? $index + 1,
                ]);
                $requestItem->forceFill(['status' => CorrectionRequestItemStatus::Pending])->save();
            }

            $this->auditLogger->record(
                event: AuditEvents::CREATE,
                auditable: $request,
                module: 'administrative_processes',
                action: 'correction_request_create',
                description: 'Pedido de aperfeiçoamento criado em rascunho.',
            );

            $request->refresh();

            return $request->load(['items']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CorrectionRequest $request, array $data, User $actor): CorrectionRequest
    {
        if ($this->requestStatus($request) !== CorrectionRequestStatus::Draft) {
            throw ValidationException::withMessages(['correction_request' => 'Apenas rascunhos podem ser editados.']);
        }

        $request->fill([
            'subject' => $data['subject'],
            'message' => $data['message'],
            'legal_basis' => $data['legal_basis'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'response_deadline_at' => $data['response_deadline_at'] ?? $request->response_deadline_at,
            'internal_notes' => $data['internal_notes'] ?? null,
        ])->save();

        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $request,
            module: 'administrative_processes',
            action: 'correction_request_update',
            description: 'Pedido de aperfeiçoamento em rascunho atualizado.',
        );

        return $request->refresh();
    }

    public function issue(CorrectionRequest $request, User $actor): CorrectionRequest
    {
        if ($request->items()->where('is_required', true)->count() === 0) {
            throw ValidationException::withMessages(['items' => 'O pedido deve ter pelo menos um item obrigatório.']);
        }

        return DB::transaction(function () use ($request, $actor) {
            $request->forceFill([
                'status' => CorrectionRequestStatus::Issued,
                'candidate_visible' => true,
                'issued_by' => $actor->id,
                'issued_at' => now(),
            ])->save();

            $process = $this->requiredAdministrativeProcess($request);
            $process->forceFill(['current_correction_request_id' => $request->id])->save();

            if ($this->processStatus($process) === AdministrativeProcessStatus::EligibilityReview) {
                $this->transitionService->transition($process, AdministrativeProcessStatus::RequiresCorrection, $actor);
                $process->refresh();
            }

            if ($this->processStatus($process) === AdministrativeProcessStatus::RequiresCorrection) {
                $this->transitionService->transition(
                    $process,
                    AdministrativeProcessStatus::AwaitingCandidateResponse,
                    $actor,
                    'Pedido de aperfeiçoamento emitido.',
                );
            }

            $this->auditLogger->record(
                event: AuditEvents::PUBLISH,
                auditable: $request,
                module: 'administrative_processes',
                action: 'correction_request_issue',
                description: 'Pedido de aperfeiçoamento emitido ao candidato.',
                metadata: ['deadline' => $this->responseDeadlineIso($request)],
            );

            return $request->refresh();
        });
    }

    public function close(CorrectionRequest $request, User $actor): CorrectionRequest
    {
        $request->forceFill([
            'status' => CorrectionRequestStatus::Closed,
            'closed_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $request, 'administrative_processes', 'correction_request_close', 'Pedido de aperfeiçoamento fechado.');

        return $request->refresh();
    }

    public function cancel(CorrectionRequest $request, User $actor): CorrectionRequest
    {
        $request->forceFill([
            'status' => CorrectionRequestStatus::Cancelled,
            'cancelled_at' => now(),
            'candidate_visible' => false,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $request, 'administrative_processes', 'correction_request_cancel', 'Pedido de aperfeiçoamento cancelado.');

        return $request->refresh();
    }

    public function markOverdue(CorrectionRequest $request, User $actor): CorrectionRequest
    {
        $request->forceFill(['status' => CorrectionRequestStatus::Overdue])->save();

        $process = $this->requiredAdministrativeProcess($request);

        if ($this->processStatus($process) === AdministrativeProcessStatus::AwaitingCandidateResponse) {
            $this->transitionService->transition($process, AdministrativeProcessStatus::CorrectionOverdue, $actor);
        }

        return $request->refresh();
    }

    private function hasOpenRequest(AdministrativeProcess $process): bool
    {
        return $process->correctionRequests()
            ->whereIn('status', [
                CorrectionRequestStatus::Draft->value,
                CorrectionRequestStatus::Issued->value,
                CorrectionRequestStatus::Open->value,
                CorrectionRequestStatus::PartiallyResponded->value,
                CorrectionRequestStatus::Responded->value,
                CorrectionRequestStatus::UnderReview->value,
            ])
            ->exists();
    }

    private function generateRequestNumber(): string
    {
        $next = CorrectionRequest::withTrashed()->count() + 1;

        do {
            $number = 'APR-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (CorrectionRequest::withTrashed()->where('request_number', $number)->exists());

        return $number;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function itemsData(array $data): array
    {
        $items = $data['items'] ?? [];

        return is_array($items) ? array_values(array_filter($items, 'is_array')) : [];
    }

    private function requiredApplication(AdministrativeProcess $process): Application
    {
        $application = $process->application;

        if (! $application instanceof Application) {
            throw ValidationException::withMessages(['application' => 'Processo sem candidatura associada.']);
        }

        return $application;
    }

    private function requiredAdministrativeProcess(CorrectionRequest $request): AdministrativeProcess
    {
        $process = $request->administrativeProcess;

        if (! $process instanceof AdministrativeProcess) {
            throw ValidationException::withMessages(['process' => 'Pedido sem processo administrativo associado.']);
        }

        return $process;
    }

    private function processStatus(AdministrativeProcess $process): ?AdministrativeProcessStatus
    {
        $status = $process->getAttribute('status');

        if ($status instanceof AdministrativeProcessStatus) {
            return $status;
        }

        return is_string($status) ? AdministrativeProcessStatus::tryFrom($status) : null;
    }

    private function requestStatus(CorrectionRequest $request): ?CorrectionRequestStatus
    {
        $status = $request->getAttribute('status');

        if ($status instanceof CorrectionRequestStatus) {
            return $status;
        }

        return is_string($status) ? CorrectionRequestStatus::tryFrom($status) : null;
    }

    private function responseDeadlineIso(CorrectionRequest $request): ?string
    {
        $deadline = $request->getAttribute('response_deadline_at');

        return $deadline instanceof CarbonInterface ? $deadline->toIso8601String() : null;
    }
}
