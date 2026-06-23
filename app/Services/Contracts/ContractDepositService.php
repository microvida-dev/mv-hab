<?php

namespace App\Services\Contracts;

use App\Enums\DepositStatus;
use App\Models\Contract;
use App\Models\ContractDeposit;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class ContractDepositService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly ContractNotificationService $notificationService,
    ) {}

    public function createForContract(Contract $contract, User $actor): ContractDeposit
    {
        $amount = (float) ($contract->deposit_amount ?? 0);

        $deposit = ContractDeposit::query()->create([
            'lease_contract_id' => $contract->id,
            'application_id' => $contract->application_id,
            'allocation_id' => $contract->allocation_id,
            'user_id' => $contract->user_id,
            'amount' => $amount,
            'currency' => 'EUR',
            'calculation_basis' => 'Caução calculada a partir da regra de renda aplicada. Sem cobrança real nesta sprint.',
        ]);
        $deposit->forceFill([
            'status' => $amount > 0 ? DepositStatus::Pending : DepositStatus::NotRequired,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $deposit, 'contracts', 'contract_deposit_create', 'Caução associada ao contrato.');

        return $deposit->refresh();
    }

    public function markRequested(ContractDeposit $deposit, User $actor): ContractDeposit
    {
        $deposit->forceFill([
            'status' => DepositStatus::Requested,
            'requested_at' => now(),
            'updated_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $deposit, 'contracts', 'contract_deposit_requested', 'Caução marcada como solicitada.');
        $this->notificationService->depositRequested($this->leaseContractForDeposit($deposit), $actor);

        return $deposit->refresh();
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function markPaid(ContractDeposit $deposit, User $actor, array $data): ContractDeposit
    {
        $deposit->forceFill([
            'status' => DepositStatus::Paid,
            'paid_at' => $data['paid_at'],
            'payment_reference' => $data['payment_reference'] ?? null,
            'receipt_reference' => $data['receipt_reference'] ?? null,
            'notes' => $data['notes'] ?? $deposit->notes,
            'updated_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $deposit, 'contracts', 'contract_deposit_paid_manual', 'Caução registada manualmente como paga.');
        $this->notificationService->depositPaidRegistered($this->leaseContractForDeposit($deposit), $actor);

        return $deposit->refresh();
    }

    public function waive(ContractDeposit $deposit, User $actor, string $reason, ?string $internalNotes = null): ContractDeposit
    {
        $deposit->forceFill([
            'status' => DepositStatus::Waived,
            'waived_at' => now(),
            'notes' => $reason,
            'internal_notes' => $internalNotes,
            'updated_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $deposit, 'contracts', 'contract_deposit_waive', 'Caução dispensada com justificação.');

        return $deposit->refresh();
    }

    public function cancel(ContractDeposit $deposit, User $actor, ?string $reason = null): ContractDeposit
    {
        $deposit->forceFill([
            'status' => DepositStatus::Cancelled,
            'cancelled_at' => now(),
            'internal_notes' => $reason ?: $deposit->internal_notes,
            'updated_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $deposit, 'contracts', 'contract_deposit_cancel', 'Caução cancelada.');

        return $deposit->refresh();
    }

    private function leaseContractForDeposit(ContractDeposit $deposit): Contract
    {
        $contract = $deposit->leaseContract;

        if (! $contract instanceof Contract) {
            throw ValidationException::withMessages([
                'contract' => 'A caução não tem contrato associado.',
            ]);
        }

        return $contract;
    }
}
