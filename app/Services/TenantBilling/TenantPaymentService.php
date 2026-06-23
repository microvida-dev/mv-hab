<?php

namespace App\Services\TenantBilling;

use App\Enums\TenantPaymentStatus;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenantPaymentService
{
    public function __construct(
        private readonly TenantInvoiceService $invoices,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function registerForInvoice(TenantInvoice $invoice, User $actor, array $data): TenantPayment
    {
        return DB::transaction(function () use ($invoice, $actor, $data) {
            $amount = (float) $data['amount'];

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'O valor do pagamento tem de ser superior a zero.']);
            }

            $payment = new TenantPayment;
            $payment->forceFill([
                'tenant_invoice_id' => $invoice->id,
                'tenant_financial_account_id' => $invoice->tenant_financial_account_id,
                'lease_contract_id' => $invoice->lease_contract_id,
                'user_id' => $invoice->user_id,
                'payment_number' => $this->paymentNumber(),
                'status' => ($data['confirm_now'] ?? false) ? TenantPaymentStatus::Confirmed : TenantPaymentStatus::Registered,
                'amount' => $amount,
                'allocated_amount' => min($amount, (float) $invoice->amount_outstanding),
                'unallocated_amount' => max(0, $amount - (float) $invoice->amount_outstanding),
                'currency' => $data['currency'] ?? 'EUR',
                'payment_date' => $data['payment_date'],
                'value_date' => $data['value_date'] ?? $data['payment_date'],
                'registered_at' => now(),
                'confirmed_at' => ($data['confirm_now'] ?? false) ? now() : null,
                'method' => $data['method'] ?? 'manual',
                'source' => $data['source'] ?? 'backoffice',
                'external_reference' => $data['external_reference'] ?? null,
                'payer_name' => $data['payer_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'registered_by' => $actor->id,
                'confirmed_by' => ($data['confirm_now'] ?? false) ? $actor->id : null,
            ])->save();

            $this->invoices->markPaymentImpact($invoice);
            $this->auditLogger->record(AuditEvents::CREATE, $payment, 'payments', 'tenant_payment_registered', 'Pagamento operacional de inquilino registado.');

            return $payment->refresh();
        });
    }

    public function confirm(TenantPayment $payment, User $actor): TenantPayment
    {
        DB::transaction(function () use ($payment, $actor): void {
            if (! in_array($payment->status, [TenantPaymentStatus::Registered, TenantPaymentStatus::Pending], true)) {
                throw ValidationException::withMessages(['payment' => 'Só pagamentos registados ou pendentes podem ser confirmados.']);
            }

            $payment->forceFill([
                'status' => TenantPaymentStatus::Confirmed,
                'confirmed_at' => now(),
                'confirmed_by' => $actor->id,
            ])->save();

            if ($payment->invoice) {
                $this->invoices->markPaymentImpact($payment->invoice);
            }

            $this->auditLogger->record(AuditEvents::APPROVE, $payment, 'payments', 'tenant_payment_confirmed', 'Pagamento operacional confirmado.');
        });

        return $payment->refresh();
    }

    private function paymentNumber(): string
    {
        return 'TPAY-'.now()->format('Ym').'-'.str_pad((string) (TenantPayment::query()->withTrashed()->count() + 1), 6, '0', STR_PAD_LEFT);
    }
}
