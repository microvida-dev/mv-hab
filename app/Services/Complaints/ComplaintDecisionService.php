<?php

namespace App\Services\Complaints;

use App\Enums\ComplaintDecisionResult;
use App\Enums\ComplaintDecisionStatus;
use App\Enums\ComplaintStatus;
use App\Enums\ListChangeType;
use App\Enums\OfficialNotificationType;
use App\Models\Application;
use App\Models\Complaint;
use App\Models\ComplaintDecision;
use App\Models\ProvisionalList;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Lists\ListChangeLogService;
use App\Services\Notifications\OfficialNotificationService;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComplaintDecisionService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly OfficialNotificationService $notificationService,
        private readonly ListChangeLogService $changeLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Complaint $complaint, array $data, User $actor): ComplaintDecision
    {
        if (! $this->complaintStatusIsIn($complaint, [ComplaintStatus::UnderReview, ComplaintStatus::AdditionalInformationSubmitted, ComplaintStatus::Received, ComplaintStatus::Submitted])) {
            throw ValidationException::withMessages(['complaint' => 'A reclamação não está em estado de decisão.']);
        }

        return DB::transaction(function () use ($complaint, $data, $actor) {
            $decision = new ComplaintDecision($data);
            $decision->forceFill([
                'complaint_id' => $complaint->id,
                'application_id' => $complaint->application_id,
                'provisional_list_id' => $complaint->provisional_list_id,
                'decision_number' => $this->generateDecisionNumber(),
                'status' => ComplaintDecisionStatus::Proposed,
                'proposed_by' => $actor->id,
                'proposed_at' => now(),
            ])->save();

            $this->auditLogger->record(AuditEvents::DECISION, $decision, 'complaints', 'complaint_decision_create', 'Proposta de decisão de reclamação criada.');

            return $decision->refresh();
        });
    }

    public function approve(ComplaintDecision $decision, User $actor): ComplaintDecision
    {
        if ($this->decisionStatus($decision) !== ComplaintDecisionStatus::Proposed) {
            throw ValidationException::withMessages(['complaint_decision' => 'A decisão deve estar proposta antes de aprovação.']);
        }

        $changeLogService = $this->changeLogService;
        $notificationService = $this->notificationService;

        return DB::transaction(function () use ($decision, $actor, $changeLogService, $notificationService) {
            $decision->forceFill([
                'status' => ComplaintDecisionStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'notified_at' => now(),
                'candidate_visible' => true,
            ])->save();

            $complaintStatus = match ($this->decisionResult($decision)) {
                ComplaintDecisionResult::Accepted => ComplaintStatus::Accepted,
                ComplaintDecisionResult::PartiallyAccepted => ComplaintStatus::PartiallyAccepted,
                ComplaintDecisionResult::Withdrawn => ComplaintStatus::Withdrawn,
                ComplaintDecisionResult::Cancelled => ComplaintStatus::Cancelled,
                default => ComplaintStatus::Rejected,
            };
            $complaint = $this->requiredComplaint($decision);
            $complaint->forceFill([
                'status' => $complaintStatus,
                'review_completed_at' => now(),
                'closed_at' => now(),
            ])->save();

            if ($this->decisionRequiresListUpdate($decision)) {
                $changeLogService->record(
                    ListChangeType::ComplaintEffect,
                    $this->requiredApplication($decision),
                    $this->optionalProvisionalList($decision),
                    actor: $actor,
                    source: $decision,
                    reason: $this->decisionSummary($decision),
                );
            }

            $notificationService->createInternal(
                user: $this->requiredCandidate($complaint),
                type: OfficialNotificationType::ComplaintDecided,
                subject: 'Decisão de reclamação disponível',
                body: $this->decisionSummary($decision),
                notifiable: $decision,
                application: $this->optionalApplication($decision),
                actor: $actor,
            );

            $this->auditLogger->record(AuditEvents::APPROVE, $decision, 'complaints', 'complaint_decision_approve', 'Decisão de reclamação aprovada.');

            return $decision->refresh();
        });
    }

    public function cancel(ComplaintDecision $decision, User $actor): ComplaintDecision
    {
        if ($this->decisionStatus($decision) === ComplaintDecisionStatus::Approved) {
            throw ValidationException::withMessages(['complaint_decision' => 'Decisões aprovadas não podem ser canceladas diretamente.']);
        }

        $decision->forceFill(['status' => ComplaintDecisionStatus::Cancelled])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $decision, 'complaints', 'complaint_decision_cancel', 'Decisão de reclamação cancelada.');

        return $decision->refresh();
    }

    private function generateDecisionNumber(): string
    {
        $next = ComplaintDecision::withTrashed()->count() + 1;

        do {
            $number = 'DEC-REC-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (ComplaintDecision::withTrashed()->where('decision_number', $number)->exists());

        return $number;
    }

    /**
     * @param  list<ComplaintStatus>  $statuses
     */
    private function complaintStatusIsIn(Complaint $complaint, array $statuses): bool
    {
        $status = $this->complaintStatus($complaint);

        return $status !== null && in_array($status, $statuses, true);
    }

    private function complaintStatus(Complaint $complaint): ?ComplaintStatus
    {
        $status = $complaint->getAttribute('status');

        if ($status instanceof ComplaintStatus) {
            return $status;
        }

        return is_string($status) ? ComplaintStatus::tryFrom($status) : null;
    }

    private function decisionStatus(ComplaintDecision $decision): ?ComplaintDecisionStatus
    {
        $status = $decision->getAttribute('status');

        if ($status instanceof ComplaintDecisionStatus) {
            return $status;
        }

        return is_string($status) ? ComplaintDecisionStatus::tryFrom($status) : null;
    }

    private function decisionResult(ComplaintDecision $decision): ?ComplaintDecisionResult
    {
        $result = $decision->getAttribute('decision_result');

        if ($result instanceof ComplaintDecisionResult) {
            return $result;
        }

        return is_string($result) ? ComplaintDecisionResult::tryFrom($result) : null;
    }

    private function decisionRequiresListUpdate(ComplaintDecision $decision): bool
    {
        return (bool) $decision->getAttribute('requires_list_update');
    }

    private function decisionSummary(ComplaintDecision $decision): string
    {
        $summary = $decision->getAttribute('summary');

        return is_string($summary) ? $summary : '';
    }

    private function requiredComplaint(ComplaintDecision $decision): Complaint
    {
        $complaint = $decision->complaint;

        if (! $complaint instanceof Complaint) {
            throw ValidationException::withMessages(['complaint' => 'A decisão não tem reclamação associada.']);
        }

        return $complaint;
    }

    private function requiredApplication(ComplaintDecision $decision): Application
    {
        $application = $decision->application;

        if (! $application instanceof Application) {
            throw ValidationException::withMessages(['application' => 'A decisão não tem candidatura associada.']);
        }

        return $application;
    }

    private function optionalApplication(ComplaintDecision $decision): ?Application
    {
        $application = $decision->application;

        return $application instanceof Application ? $application : null;
    }

    private function optionalProvisionalList(ComplaintDecision $decision): ?ProvisionalList
    {
        $list = $decision->provisionalList;

        return $list instanceof ProvisionalList ? $list : null;
    }

    private function requiredCandidate(Complaint $complaint): User
    {
        $candidate = $complaint->candidate;

        if (! $candidate instanceof User) {
            throw ValidationException::withMessages(['candidate' => 'A reclamação não tem candidato associado.']);
        }

        return $candidate;
    }
}
