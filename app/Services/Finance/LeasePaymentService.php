<?php

namespace App\Services\Finance;

use App\Enums\FinancialTransactionType;
use App\Enums\LeasePaymentStatus;
use App\Enums\PaymentAllocationStatus;
use App\Models\LeasePayment;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeasePaymentService
{
    public function __construct(
        private readonly FinanceNumberService $numbers,
        private readonly FinancialTransactionService $transactions,
        private readonly FinanceNotificationService $notifications,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function store(TenantFinancialAccount $account, User $actor, array $data): LeasePayment
    {
        return DB::transaction(function () use ($account, $actor, $data) {
            $amount = (float) $data['amount'];
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'O valor do pagamento tem de ser superior a zero.']);
            }

            $payment = new LeasePayment;
            $payment->forceFill([
                'tenant_financial_account_id' => $account->id,
                'lease_contract_id' => $account->lease_contract_id,
                'user_id' => $account->user_id,
                'status' => LeasePaymentStatus::Pending,
                'payment_number' => $this->numbers->paymentNumber(),
                'amount' => $amount,
                'allocated_amount' => 0,
                'unallocated_amount' => $amount,
                'payment_date' => $data['payment_date'],
                'value_date' => $data['value_date'] ?? $data['payment_date'],
                'received_at' => now(),
                'method' => $data['method'] ?? 'manual',
                'source' => $data['source'] ?? 'backoffice',
                'external_reference' => $data['external_reference'] ?? null,
                'payer_name' => $data['payer_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::CREATE, $payment, 'finance', 'lease_payment_store', 'Pagamento de renda registado.');

            if (($data['confirm_now'] ?? false) === true) {
                return $this->confirm($payment, $actor);
            }

            return $payment->refresh();
        });
    }

    public function confirm(LeasePayment $payment, User $actor): LeasePayment
    {
        return DB::transaction(function () use ($payment, $actor) {
            if (! $this->paymentHasStatus($payment, [LeasePaymentStatus::Pending, LeasePaymentStatus::Draft])) {
                throw ValidationException::withMessages(['payment' => 'Só pagamentos pendentes podem ser confirmados.']);
            }

            $payment->forceFill([
                'status' => LeasePaymentStatus::Confirmed,
                'confirmed_at' => now(),
                'confirmed_by' => $actor->id,
            ])->save();

            $account = $this->accountForPayment($payment);
            $this->transactions->record($account, FinancialTransactionType::PaymentReceived, (float) $payment->amount * -1, $payment, $actor, 'Pagamento confirmado.');
            $this->auditLogger->record(AuditEvents::APPROVE, $payment, 'finance', 'lease_payment_confirm', 'Pagamento de renda confirmado manualmente.');
            $this->notifications->leasePaymentRegistered($payment->refresh(), $actor);

            return $payment->refresh();
        });
    }

    public function reverse(LeasePayment $payment, User $actor, string $reason): LeasePayment
    {
        return DB::transaction(function () use ($payment, $actor, $reason) {
            if ($this->paymentHasStatus($payment, [LeasePaymentStatus::Reversed])) {
                throw ValidationException::withMessages(['payment' => 'O pagamento já está estornado.']);
            }

            foreach ($payment->allocations()->where('status', PaymentAllocationStatus::Active)->get() as $allocation) {
                app(PaymentAllocationService::class)->reverse($allocation, $actor, $reason);
            }

            $payment->forceFill([
                'status' => LeasePaymentStatus::Reversed,
                'reversed_at' => now(),
                'reversed_by' => $actor->id,
                'reversal_reason' => $reason,
            ])->save();

            $this->transactions->record($this->accountForPayment($payment), FinancialTransactionType::PaymentReversed, (float) $payment->amount, $payment, $actor, 'Pagamento estornado.');
            $this->auditLogger->record(AuditEvents::UPDATE, $payment, 'finance', 'lease_payment_reverse', 'Pagamento de renda estornado.', metadata: ['reason' => $reason]);

            return $payment->refresh();
        });
    }

    private function accountForPayment(LeasePayment $payment): TenantFinancialAccount
    {
        $account = $payment->tenantFinancialAccount;

        if (! $account instanceof TenantFinancialAccount) {
            throw ValidationException::withMessages([
                'tenant_financial_account' => 'O pagamento não tem conta financeira associada.',
            ]);
        }

        return $account;
    }

    /**
     * @param  array<int, LeasePaymentStatus>  $statuses
     */
    private function paymentHasStatus(LeasePayment $payment, array $statuses): bool
    {
        $status = $payment->getAttribute('status');

        foreach ($statuses as $expected) {
            if ($status === $expected || $status === $expected->value) {
                return true;
            }
        }

        return false;
    }
}
