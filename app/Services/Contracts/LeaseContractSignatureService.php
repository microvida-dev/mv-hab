<?php

namespace App\Services\Contracts;

use App\Enums\ContractSignatureMethod;
use App\Enums\ContractSignatureRole;
use App\Enums\ContractSignatureStatus;
use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\LeaseContractSignature;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class LeaseContractSignatureService
{
    public function __construct(
        private readonly LeaseContractStatusService $statusService,
        private readonly ContractNotificationService $notificationService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(Contract $contract, User $actor, array $data): LeaseContractSignature
    {
        $signature = $contract->signatures()->create([
            'user_id' => $contract->user_id,
            'signature_role' => ContractSignatureRole::from($data['signature_role']),
            'signed_by_name' => $data['signed_by_name'],
            'signature_method' => ContractSignatureMethod::from($data['signature_method']),
            'signature_reference' => $data['signature_reference'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        $signature->forceFill([
            'status' => ContractSignatureStatus::Signed,
            'signed_at' => $data['signed_at'],
        ])->save();

        if ($contract->status === ContractStatus::Issued) {
            $this->statusService->transition($contract, ContractStatus::Signed, $actor, 'Registo manual de assinatura.');
        }

        $this->auditLogger->record(AuditEvents::CREATE, $signature, 'contracts', 'lease_contract_signature_store', 'Assinatura/registo manual do contrato criado.');
        $this->notificationService->signed($contract->refresh(), $actor);

        return $signature->refresh();
    }
}
