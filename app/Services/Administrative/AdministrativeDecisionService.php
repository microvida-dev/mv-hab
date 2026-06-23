<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeDecisionResult;
use App\Enums\AdministrativeDecisionStatus;
use App\Enums\AdministrativeDecisionType;
use App\Enums\AdministrativeProcessStatus;
use App\Models\AdministrativeDecision;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdministrativeDecisionService
{
    public function __construct(
        private readonly AdministrativeWorkflowConfigResolver $configResolver,
        private readonly AdministrativeWorkflowTransitionService $transitionService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(AdministrativeProcess $process, array $data, User $actor): AdministrativeDecision
    {
        $config = $this->configResolver->resolveForApplication($this->requiredApplication($process));
        $status = $config->requires_decision_approval
            ? AdministrativeDecisionStatus::Proposed
            : AdministrativeDecisionStatus::Approved;

        return DB::transaction(function () use ($process, $data, $actor, $status) {
            $decision = new AdministrativeDecision([
                'summary' => $data['summary'],
                'legal_basis' => $data['legal_basis'] ?? null,
                'grounds' => $data['grounds'],
                'candidate_visible' => (bool) ($data['candidate_visible'] ?? false),
            ]);
            $decision->forceFill([
                'administrative_process_id' => $process->id,
                'application_id' => $process->application_id,
                'decision_type' => $data['decision_type'],
                'decision_result' => $data['decision_result'],
                'status' => $status,
                'decided_by' => $actor->id,
                'decided_at' => now(),
                'approved_by' => $status === AdministrativeDecisionStatus::Approved ? $actor->id : null,
                'approved_at' => $status === AdministrativeDecisionStatus::Approved ? now() : null,
            ]);
            $decision->save();

            $this->auditLogger->record(
                event: AuditEvents::DECISION,
                auditable: $decision,
                module: 'administrative_processes',
                action: 'decision_create',
                description: 'Decisão administrativa registada.',
                newValues: ['result' => $this->decisionResultValue($decision), 'status' => $this->decisionStatusValue($decision)],
            );

            if ($this->decisionStatus($decision) === AdministrativeDecisionStatus::Approved) {
                $this->apply($decision, $actor);
            }

            $decision->refresh();

            return $decision->load(['administrativeProcess', 'decidedBy', 'approvedBy']);
        });
    }

    public function approve(AdministrativeDecision $decision, User $actor): AdministrativeDecision
    {
        return DB::transaction(function () use ($decision, $actor) {
            $decision->forceFill([
                'status' => AdministrativeDecisionStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ])->save();

            $this->apply($decision, $actor);

            $this->auditLogger->record(
                event: AuditEvents::APPROVE,
                auditable: $decision,
                module: 'administrative_processes',
                action: 'decision_approve',
                description: 'Decisão administrativa aprovada.',
            );

            return $decision->refresh();
        });
    }

    public function cancel(AdministrativeDecision $decision, User $actor): AdministrativeDecision
    {
        $decision->forceFill(['status' => AdministrativeDecisionStatus::Cancelled])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $decision, 'administrative_processes', 'decision_cancel', 'Decisão administrativa cancelada.');

        return $decision->refresh();
    }

    private function apply(AdministrativeDecision $decision, User $actor): void
    {
        $process = $this->requiredAdministrativeProcess($decision);
        $target = match ($this->decisionResult($decision)) {
            AdministrativeDecisionResult::AdmittedForScoring => AdministrativeProcessStatus::AdmittedForScoring,
            AdministrativeDecisionResult::NotAdmitted => AdministrativeProcessStatus::NotAdmitted,
            default => null,
        };

        if ($target === null) {
            return;
        }

        $processStatus = $this->processStatus($process);

        if ($processStatus !== AdministrativeProcessStatus::EligibilityReview
            && $processStatus !== AdministrativeProcessStatus::PreliminaryReview) {
            return;
        }

        $process->forceFill([
            'decision_summary' => $this->decisionSummary($decision),
            'legal_basis' => $this->decisionLegalBasis($decision),
        ])->save();

        $this->transitionService->transition($process, $target, $actor, $this->decisionGrounds($decision));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function admissionData(array $data): array
    {
        return [
            ...$data,
            'decision_type' => AdministrativeDecisionType::AdmissionForScoring->value,
            'decision_result' => AdministrativeDecisionResult::AdmittedForScoring->value,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function nonAdmissionData(array $data): array
    {
        return [
            ...$data,
            'decision_type' => AdministrativeDecisionType::NonAdmission->value,
            'decision_result' => AdministrativeDecisionResult::NotAdmitted->value,
        ];
    }

    private function requiredApplication(AdministrativeProcess $process): Application
    {
        $application = $process->application;

        if (! $application instanceof Application) {
            throw ValidationException::withMessages(['application' => 'Processo sem candidatura associada.']);
        }

        return $application;
    }

    private function requiredAdministrativeProcess(AdministrativeDecision $decision): AdministrativeProcess
    {
        $process = $decision->administrativeProcess;

        if (! $process instanceof AdministrativeProcess) {
            throw ValidationException::withMessages(['process' => 'Decisão sem processo administrativo associado.']);
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

    private function decisionStatus(AdministrativeDecision $decision): ?AdministrativeDecisionStatus
    {
        $status = $decision->getAttribute('status');

        if ($status instanceof AdministrativeDecisionStatus) {
            return $status;
        }

        return is_string($status) ? AdministrativeDecisionStatus::tryFrom($status) : null;
    }

    private function decisionResult(AdministrativeDecision $decision): ?AdministrativeDecisionResult
    {
        $result = $decision->getAttribute('decision_result');

        if ($result instanceof AdministrativeDecisionResult) {
            return $result;
        }

        return is_string($result) ? AdministrativeDecisionResult::tryFrom($result) : null;
    }

    private function decisionStatusValue(AdministrativeDecision $decision): string
    {
        $status = $this->decisionStatus($decision);

        return $status instanceof AdministrativeDecisionStatus ? $status->value : '';
    }

    private function decisionResultValue(AdministrativeDecision $decision): string
    {
        $result = $this->decisionResult($decision);

        return $result instanceof AdministrativeDecisionResult ? $result->value : '';
    }

    private function decisionSummary(AdministrativeDecision $decision): ?string
    {
        $summary = $decision->getAttribute('summary');

        return is_string($summary) ? $summary : null;
    }

    private function decisionLegalBasis(AdministrativeDecision $decision): ?string
    {
        $legalBasis = $decision->getAttribute('legal_basis');

        return is_string($legalBasis) ? $legalBasis : null;
    }

    private function decisionGrounds(AdministrativeDecision $decision): ?string
    {
        $grounds = $decision->getAttribute('grounds');

        return is_string($grounds) ? $grounds : null;
    }
}
