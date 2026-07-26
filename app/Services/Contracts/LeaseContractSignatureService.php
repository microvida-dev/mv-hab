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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        return DB::transaction(function () use ($contract, $actor, $data): LeaseContractSignature {
            /** @var Contract $locked */
            $locked = Contract::query()
                ->lockForUpdate()
                ->findOrFail($contract->getKey());

            if (! in_array($locked->status, [ContractStatus::Issued, ContractStatus::Signed], true)) {
                throw ValidationException::withMessages([
                    'contract' => 'A assinatura só pode ser registada num contrato emitido ou assinado.',
                ]);
            }

            $role = ContractSignatureRole::from($data['signature_role']);
            $method = ContractSignatureMethod::from($data['signature_method']);
            $existing = $locked->signatures()
                ->where('signature_role', $role->value)
                ->where('signed_by_name', $data['signed_by_name'])
                ->where('signature_method', $method->value)
                ->where('signed_at', $data['signed_at'])
                ->where('signature_reference', $data['signature_reference'] ?? null)
                ->lockForUpdate()
                ->first();

            if ($existing instanceof LeaseContractSignature) {
                return $existing;
            }

            $signature = $locked->signatures()->create([
                'user_id' => $locked->user_id,
                'signature_role' => $role,
                'signed_by_name' => $data['signed_by_name'],
                'signature_method' => $method,
                'signature_reference' => $data['signature_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $signature->forceFill([
                'status' => ContractSignatureStatus::Signed,
                'signed_at' => $data['signed_at'],
            ])->save();

            if ($locked->status === ContractStatus::Issued) {
                $this->statusService->transition(
                    $locked,
                    ContractStatus::Signed,
                    $actor,
                    'Registo manual de assinatura.',
                );
            }

            $this->auditLogger->record(
                AuditEvents::CREATE,
                $signature,
                'contracts',
                'lease_contract_signature_store',
                'Assinatura/registo manual do contrato criado.',
                metadata: ['actor_id' => $actor->id],
            );
            $this->notificationService->signed($locked->refresh(), $actor);

            return $signature->refresh();
        });
    }
}
