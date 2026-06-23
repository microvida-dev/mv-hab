<?php

namespace App\Services\Finance;

use App\Enums\ContractStatus;
use App\Enums\FinancialAccountStatus;
use App\Models\Contract;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class TenantFinancialAccountService
{
    public function __construct(
        private readonly FinanceNumberService $numbers,
        private readonly FinancialTransactionService $transactions,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function ensureForContract(Contract $contract, User $actor): TenantFinancialAccount
    {
        $existing = $contract->financialAccount()->first();

        if ($existing) {
            return $existing;
        }

        if ($contract->status !== ContractStatus::Active) {
            throw ValidationException::withMessages([
                'lease_contract_id' => 'A conta financeira só pode ser criada para contratos ativos.',
            ]);
        }

        $account = new TenantFinancialAccount;
        $account->forceFill([
            'lease_contract_id' => $contract->id,
            'application_id' => $contract->application_id,
            'allocation_id' => $contract->allocation_id,
            'user_id' => $contract->user_id,
            'household_id' => $contract->household_id,
            'housing_unit_id' => $contract->housing_unit_id,
            'account_number' => $this->numbers->accountNumber(),
            'status' => FinancialAccountStatus::Active,
            'currency' => 'EUR',
            'opened_at' => now(),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $account, 'finance', 'financial_account_create', 'Conta financeira criada para contrato ativo.');

        return $this->transactions->recalculateAccount($account);
    }

    public function recalculate(TenantFinancialAccount $account): TenantFinancialAccount
    {
        return $this->transactions->recalculateAccount($account);
    }
}
