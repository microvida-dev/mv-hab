<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeProcessStatus;
use App\Models\AdministrativeProcess;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdministrativeWorkflowTransitionService
{
    private const ALLOWED = [
        'submitted' => ['received'],
        'received' => ['assigned'],
        'assigned' => ['preliminary_review'],
        'preliminary_review' => ['document_review', 'not_admitted'],
        'document_review' => ['eligibility_review'],
        'eligibility_review' => ['requires_correction', 'admitted_for_scoring', 'not_admitted'],
        'requires_correction' => ['awaiting_candidate_response'],
        'awaiting_candidate_response' => ['correction_submitted', 'correction_overdue'],
        'correction_submitted' => ['correction_under_review'],
        'correction_under_review' => ['eligibility_review'],
        'admitted_for_scoring' => ['archived'],
        'not_admitted' => ['archived'],
    ];

    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function recordInitial(
        AdministrativeProcess $process,
        AdministrativeProcessStatus $to,
        User $actor,
        ?string $reason = null,
    ): void {
        $process->statusHistories()->create([
            'from_status' => null,
            'to_status' => $to->value,
            'changed_by' => $actor->id,
            'reason' => $reason,
        ]);
    }

    public function transition(
        AdministrativeProcess $process,
        AdministrativeProcessStatus $to,
        User $actor,
        ?string $reason = null,
    ): AdministrativeProcess {
        return DB::transaction(function () use ($process, $to, $actor, $reason) {
            $process->refresh();
            $from = $this->processStatus($process);

            if (! $from instanceof AdministrativeProcessStatus) {
                throw ValidationException::withMessages([
                    'status' => 'O processo administrativo não tem estado válido.',
                ]);
            }

            if ($from === $to) {
                return $process;
            }

            if (! $this->canTransition($from, $to)) {
                throw ValidationException::withMessages([
                    'status' => 'A transição administrativa solicitada não é permitida.',
                ]);
            }

            $timestamps = match ($to) {
                AdministrativeProcessStatus::Received => ['received_at' => now()],
                AdministrativeProcessStatus::Assigned => ['assigned_at' => $process->assigned_at ?? now()],
                AdministrativeProcessStatus::PreliminaryReview => ['preliminary_review_started_at' => now()],
                AdministrativeProcessStatus::DocumentReview => ['document_review_started_at' => now()],
                AdministrativeProcessStatus::EligibilityReview => ['eligibility_review_started_at' => now()],
                AdministrativeProcessStatus::AdmittedForScoring => ['admitted_for_scoring_at' => now()],
                AdministrativeProcessStatus::NotAdmitted => ['not_admitted_at' => now()],
                AdministrativeProcessStatus::Withdrawn => ['withdrawn_at' => now()],
                AdministrativeProcessStatus::Cancelled => ['cancelled_at' => now()],
                AdministrativeProcessStatus::Archived => ['archived_at' => now()],
                default => [],
            };

            $process->forceFill([
                'status' => $to,
                'updated_by' => $actor->id,
                ...$timestamps,
            ])->save();

            $process->statusHistories()->create([
                'from_status' => $from->value,
                'to_status' => $to->value,
                'changed_by' => $actor->id,
                'reason' => $reason,
            ]);

            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $process,
                module: 'administrative_processes',
                action: 'status_transition',
                description: 'Estado administrativo alterado.',
                oldValues: ['status' => $from->value],
                newValues: ['status' => $to->value],
                metadata: ['reason_provided' => filled($reason)],
            );

            return $process->refresh();
        });
    }

    public function canTransition(AdministrativeProcessStatus $from, AdministrativeProcessStatus $to): bool
    {
        if (in_array($to, [AdministrativeProcessStatus::Cancelled, AdministrativeProcessStatus::Withdrawn], true)) {
            return ! $from->isFinal();
        }

        if ($from->isFinal() && $to !== AdministrativeProcessStatus::Archived) {
            return false;
        }

        return in_array($to->value, self::ALLOWED[$from->value] ?? [], true);
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
