<?php

namespace App\Services\Contracts;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class LeaseContractStatusService
{
    private const ALLOWED = [
        'preparation' => ['issued', 'cancelled'],
        'issued' => ['signed', 'cancelled'],
        'signed' => ['active', 'cancelled'],
        'active' => ['suspended', 'terminated', 'renewed', 'expired'],
        'suspended' => ['active', 'terminated'],
    ];

    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function transition(Contract $contract, ContractStatus $target, User $actor, ?string $reason = null): Contract
    {
        $from = $contract->status;
        $fromValue = $from->value;

        if ($fromValue === $target->value) {
            $contract->statusHistories()->create([
                'from_status' => $fromValue,
                'to_status' => $target->value,
                'changed_by' => $actor->id,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            return $contract->refresh();
        }

        if (! in_array($target->value, self::ALLOWED[$fromValue] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'Transição de estado contratual não permitida.']);
        }

        $payload = ['status' => $target];

        if ($target === ContractStatus::Issued) {
            $payload['issued_at'] = now();
            $payload['issued_by'] = $actor->id;
        }

        if ($target === ContractStatus::Signed) {
            $payload['signed_at'] = now();
            $payload['signed_by'] = $actor->id;
        }

        if ($target === ContractStatus::Active) {
            $payload['activated_at'] = now();
            $payload['activated_by'] = $actor->id;
            $payload['suspended_at'] = null;
        }

        if ($target === ContractStatus::Suspended) {
            $payload['suspended_at'] = now();
        }

        if ($target === ContractStatus::Terminated) {
            $payload['terminated_at'] = now();
        }

        if ($target === ContractStatus::Cancelled) {
            $payload['cancelled_at'] = now();
        }

        $contract->forceFill($payload)->save();
        $contract->statusHistories()->create([
            'from_status' => $fromValue,
            'to_status' => $target->value,
            'changed_by' => $actor->id,
            'reason' => $reason,
            'created_at' => now(),
        ]);

        $this->auditLogger->record(AuditEvents::UPDATE, $contract, 'contracts', 'lease_contract_status_'.$target->value, 'Estado do contrato atualizado.');

        return $contract->refresh();
    }
}
