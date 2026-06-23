<?php

namespace App\Services\Contracts;

use App\Enums\ContractValidationStatus;
use App\Enums\ContractValidationType;
use App\Models\Contract;
use App\Models\LeaseContractValidation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class LeaseContractValidationService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function approve(Contract $contract, User $actor, array $data): LeaseContractValidation
    {
        $validation = $contract->validations()->create([
            'validation_type' => ContractValidationType::from($data['validation_type']),
            'summary' => $data['summary'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
        ]);
        $validation->forceFill([
            'validated_by' => $actor->id,
            'status' => ContractValidationStatus::Approved,
            'validated_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::APPROVE, $validation, 'contracts', 'lease_contract_validation_approve', 'Validação interna do contrato aprovada.');

        return $validation->refresh();
    }

    public function reject(LeaseContractValidation $validation, User $actor, string $reason): LeaseContractValidation
    {
        $validation->forceFill([
            'validated_by' => $actor->id,
            'status' => ContractValidationStatus::Rejected,
            'rejection_reason' => $reason,
            'validated_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::REJECT, $validation, 'contracts', 'lease_contract_validation_reject', 'Validação interna do contrato rejeitada.');

        return $validation->refresh();
    }
}
