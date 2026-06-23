<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeProcessStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdministrativeProcessService
{
    public function __construct(
        private readonly AdministrativeWorkflowTransitionService $transitionService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function createForApplication(Application $application, User $actor): AdministrativeProcess
    {
        if ($application->administrativeProcess()->exists()) {
            throw ValidationException::withMessages([
                'application' => 'Esta candidatura já tem processo administrativo.',
            ]);
        }

        if (! in_array($application->status->value, ['submitted', 'under_review', 'correction_submitted'], true)) {
            throw ValidationException::withMessages([
                'application' => 'Só candidaturas submetidas ou em análise podem originar processo administrativo.',
            ]);
        }

        return DB::transaction(function () use ($application, $actor) {
            $application->loadMissing(['program', 'contest', 'user']);

            $process = new AdministrativeProcess;
            $process->forceFill([
                'process_number' => $this->generateProcessNumber(),
                'application_id' => $application->id,
                'program_id' => $application->program_id,
                'contest_id' => $application->contest_id,
                'user_id' => $application->user_id,
                'status' => AdministrativeProcessStatus::Received,
                'received_at' => now(),
                'summary' => 'Processo criado a partir de candidatura submetida.',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $process->save();

            $this->transitionService->recordInitial(
                $process,
                AdministrativeProcessStatus::Received,
                $actor,
                'Receção administrativa inicial.',
            );

            $this->auditLogger->record(
                event: AuditEvents::CREATE,
                auditable: $process,
                module: 'administrative_processes',
                action: 'create',
                description: 'Processo administrativo criado para candidatura submetida.',
                newValues: ['status' => AdministrativeProcessStatus::Received->value],
                metadata: ['application_id' => $application->id],
            );

            $process->refresh();

            return $process->load(['application', 'candidate', 'contest', 'program']);
        });
    }

    public function assign(AdministrativeProcess $process, User $assignee, User $actor): AdministrativeProcess
    {
        if ($process->isClosed()) {
            throw ValidationException::withMessages(['process' => 'Processos encerrados não podem ser atribuídos.']);
        }

        return DB::transaction(function () use ($process, $assignee, $actor) {
            $process->forceFill([
                'assigned_to' => $assignee->id,
                'assigned_at' => now(),
                'updated_by' => $actor->id,
            ])->save();

            if ($this->processStatus($process) === AdministrativeProcessStatus::Received) {
                $process = $this->transitionService->transition(
                    $process,
                    AdministrativeProcessStatus::Assigned,
                    $actor,
                    'Processo atribuído a técnico responsável.',
                );
            }

            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $process,
                module: 'administrative_processes',
                action: 'assign',
                description: 'Processo administrativo atribuído.',
                metadata: ['assigned_to' => $assignee->id],
            );

            return $process->refresh();
        });
    }

    public function startPreliminaryReview(AdministrativeProcess $process, User $actor): AdministrativeProcess
    {
        return $this->transitionService->transition($process, AdministrativeProcessStatus::PreliminaryReview, $actor);
    }

    public function startDocumentReview(AdministrativeProcess $process, User $actor): AdministrativeProcess
    {
        return $this->transitionService->transition($process, AdministrativeProcessStatus::DocumentReview, $actor);
    }

    public function startEligibilityReview(AdministrativeProcess $process, User $actor): AdministrativeProcess
    {
        return $this->transitionService->transition($process, AdministrativeProcessStatus::EligibilityReview, $actor);
    }

    public function transition(
        AdministrativeProcess $process,
        AdministrativeProcessStatus $status,
        User $actor,
        ?string $reason = null,
    ): AdministrativeProcess {
        return $this->transitionService->transition($process, $status, $actor, $reason);
    }

    private function generateProcessNumber(): string
    {
        $next = AdministrativeProcess::withTrashed()->count() + 1;

        do {
            $number = 'PROC-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (AdministrativeProcess::withTrashed()->where('process_number', $number)->exists());

        return $number;
    }

    private function processStatus(AdministrativeProcess $process): ?AdministrativeProcessStatus
    {
        $status = $process->getAttribute('status');

        if ($status instanceof AdministrativeProcessStatus) {
            return $status;
        }

        return is_string($status) ? AdministrativeProcessStatus::tryFrom($status) : null;
    }
}
