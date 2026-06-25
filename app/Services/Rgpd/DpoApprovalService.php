<?php

namespace App\Services\Rgpd;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\RgpdApproval;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DpoApprovalService
{
    public function __construct(private readonly AuditTrailService $audit) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function request(Model $approvable, User $actor, string $flowType, string $justification, array $metadata = []): RgpdApproval
    {
        if (trim($justification) === '') {
            throw new DomainException('A aprovação DPO exige justificação.');
        }

        $approval = RgpdApproval::query()->create([
            'approval_number' => 'DPO-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'flow_type' => $flowType,
            'status' => RgpdApproval::STATUS_PENDING_DPO_APPROVAL,
            'approvable_type' => $approvable->getMorphClass(),
            'approvable_id' => $approvable->getKey(),
            'requested_by' => $actor->id,
            'justification' => $justification,
            'metadata' => $metadata,
            'requested_at' => now(),
        ]);

        $this->audit->record(
            $this->requestedEventFor($flowType),
            $approvable,
            AuditEventCategory::Rgpd,
            AuditEventSeverity::Warning,
            $justification,
            metadata: [
                'rgpd_approval_id' => $approval->id,
                'flow_type' => $flowType,
            ],
            actor: $actor,
        );

        return $approval->refresh();
    }

    public function approve(RgpdApproval $approval, User $actor, string $notes): RgpdApproval
    {
        if ($approval->status !== RgpdApproval::STATUS_PENDING_DPO_APPROVAL) {
            throw new DomainException('Apenas pedidos pendentes podem ser aprovados pelo DPO.');
        }

        $approval->forceFill([
            'status' => RgpdApproval::STATUS_APPROVED,
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'decision_notes' => $notes,
        ])->save();

        $this->audit->record(
            'dpo_approval_approved',
            $approval,
            AuditEventCategory::Rgpd,
            AuditEventSeverity::Warning,
            'Aprovação DPO concedida.',
            metadata: [
                'flow_type' => $approval->flow_type,
            ],
            actor: $actor,
        );

        return $approval->refresh();
    }

    public function markExecuted(RgpdApproval $approval, User $actor): RgpdApproval
    {
        if ($approval->status !== RgpdApproval::STATUS_APPROVED) {
            throw new DomainException('Apenas aprovações DPO aprovadas podem ser executadas.');
        }

        $approval->forceFill([
            'status' => RgpdApproval::STATUS_EXECUTED,
            'executed_by' => $actor->id,
            'executed_at' => now(),
        ])->save();

        $this->audit->record(
            'dpo_approval_executed',
            $approval,
            AuditEventCategory::Rgpd,
            AuditEventSeverity::Warning,
            'Aprovação DPO marcada como executada.',
            metadata: [
                'flow_type' => $approval->flow_type,
            ],
            actor: $actor,
        );

        return $approval->refresh();
    }

    private function requestedEventFor(string $flowType): string
    {
        return match ($flowType) {
            'rgpd_export' => 'rgpd_export_requested',
            'rgpd_anonymization' => 'rgpd_anonymization_requested',
            'retention_execution' => 'rgpd_retention_approval_requested',
            'sensitive_audit_export' => 'sensitive_export_created',
            default => 'dpo_approval_requested',
        };
    }
}
