<?php

namespace App\Services\Contracts;

use App\Enums\ContractValidationStatus;
use App\Enums\ContractValidationType;
use App\Models\Contract;
use App\Models\LeaseContractValidation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaseContractValidationService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function approve(
        Contract $contract,
        User $actor,
        array $data,
        ?LeaseContractValidation $validation = null,
    ): LeaseContractValidation {
        return DB::transaction(function () use ($contract, $actor, $data, $validation): LeaseContractValidation {
            /** @var Contract $lockedContract */
            $lockedContract = Contract::query()
                ->lockForUpdate()
                ->findOrFail($contract->getKey());
            $type = ContractValidationType::from($data['validation_type']);

            if ($validation instanceof LeaseContractValidation) {
                /** @var LeaseContractValidation $lockedValidation */
                $lockedValidation = LeaseContractValidation::query()
                    ->where('lease_contract_id', $lockedContract->id)
                    ->lockForUpdate()
                    ->findOrFail($validation->getKey());
            } else {
                $lockedValidation = $lockedContract->validations()
                    ->where('validation_type', $type->value)
                    ->where('status', ContractValidationStatus::Approved->value)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedValidation instanceof LeaseContractValidation) {
                    $lockedValidation = $lockedContract->validations()->create([
                        'validation_type' => $type,
                        'summary' => $data['summary'] ?? null,
                        'internal_notes' => $data['internal_notes'] ?? null,
                    ]);
                }
            }

            if ($lockedValidation->status === ContractValidationStatus::Approved) {
                return $lockedValidation;
            }

            if ($lockedValidation->status === ContractValidationStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'validation' => 'Uma validação cancelada não pode ser aprovada.',
                ]);
            }

            $lockedValidation->fill([
                'validation_type' => $type,
                'summary' => $data['summary'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);
            $lockedValidation->forceFill([
                'validated_by' => $actor->id,
                'status' => ContractValidationStatus::Approved,
                'rejection_reason' => null,
                'validated_at' => now(),
            ])->save();

            $this->auditLogger->record(
                AuditEvents::APPROVE,
                $lockedValidation,
                'contracts',
                'lease_contract_validation_approve',
                'Validação interna do contrato aprovada.',
                metadata: ['actor_id' => $actor->id],
            );

            return $lockedValidation->refresh();
        });
    }

    public function reject(LeaseContractValidation $validation, User $actor, string $reason): LeaseContractValidation
    {
        return DB::transaction(function () use ($validation, $actor, $reason): LeaseContractValidation {
            /** @var LeaseContractValidation $locked */
            $locked = LeaseContractValidation::query()
                ->lockForUpdate()
                ->findOrFail($validation->getKey());

            if ($locked->status === ContractValidationStatus::Rejected) {
                return $locked;
            }

            if ($locked->status === ContractValidationStatus::Approved) {
                throw ValidationException::withMessages([
                    'validation' => 'Uma validação aprovada não pode ser rejeitada.',
                ]);
            }

            $locked->forceFill([
                'validated_by' => $actor->id,
                'status' => ContractValidationStatus::Rejected,
                'rejection_reason' => $reason,
                'validated_at' => now(),
            ])->save();

            $this->auditLogger->record(
                AuditEvents::REJECT,
                $locked,
                'contracts',
                'lease_contract_validation_reject',
                'Validação interna do contrato rejeitada.',
                metadata: ['actor_id' => $actor->id],
            );

            return $locked->refresh();
        });
    }
}
