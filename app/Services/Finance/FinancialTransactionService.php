<?php

namespace App\Services\Finance;

use App\Enums\FinancialTransactionType;
use App\Enums\PaymentAllocationStatus;
use App\Enums\RentInstallmentStatus;
use App\Models\FinancialTransaction;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FinancialTransactionService
{
    public function record(TenantFinancialAccount $account, FinancialTransactionType $type, float $amount, ?Model $transactionable, ?User $actor, ?string $description = null): FinancialTransaction
    {
        $account = $this->recalculateAccount($account);

        return FinancialTransaction::query()->create([
            'tenant_financial_account_id' => $account->id,
            'lease_contract_id' => $account->lease_contract_id,
            'user_id' => $account->user_id,
            'transaction_type' => $type,
            'amount' => $amount,
            'balance_after' => $account->current_balance,
            'currency' => $account->currency,
            'description' => $description,
            'transactionable_type' => $transactionable?->getMorphClass(),
            'transactionable_id' => $transactionable?->getKey(),
            'occurred_at' => now(),
            'created_by' => $actor?->id,
        ]);
    }

    public function recalculateAccount(TenantFinancialAccount $account): TenantFinancialAccount
    {
        $installments = $account->rentInstallments()
            ->whereNotIn('status', [RentInstallmentStatus::Cancelled->value])
            ->get();

        $totalIssued = (float) $installments->sum('amount_due');
        $totalPaid = (float) $account->leasePayments()
            ->whereIn('status', ['confirmed', 'partially_allocated', 'allocated'])
            ->sum('amount');
        $totalWaived = (float) $installments->sum('amount_waived');
        $totalOverdue = (float) $installments
            ->where('status', RentInstallmentStatus::Overdue)
            ->sum('amount_outstanding');
        $allocated = (float) $account->leasePayments()
            ->join('payment_allocations', 'lease_payments.id', '=', 'payment_allocations.lease_payment_id')
            ->where('payment_allocations.status', PaymentAllocationStatus::Active->value)
            ->sum('payment_allocations.amount');

        $account->forceFill([
            'total_issued' => $totalIssued,
            'total_paid' => $totalPaid,
            'total_overdue' => $totalOverdue,
            'total_waived' => $totalWaived,
            'current_balance' => max(0, $totalIssued - $allocated - $totalWaived),
            'next_due_date' => $account->rentInstallments()
                ->where('amount_outstanding', '>', 0)
                ->whereNotIn('status', [RentInstallmentStatus::Cancelled->value, RentInstallmentStatus::Waived->value])
                ->orderBy('due_date')
                ->value('due_date'),
        ])->save();

        return $account->refresh();
    }
}
