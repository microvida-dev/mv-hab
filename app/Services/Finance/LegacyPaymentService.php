<?php

namespace App\Services\Finance;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use App\Support\DecimalMoney;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LegacyPaymentService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Payment
    {
        return DB::transaction(function () use ($data): Payment {
            $payment = Payment::query()->create($this->normalized($data));

            $this->auditLogger->record(
                AuditEvents::CREATE,
                $payment,
                'payments',
                'legacy_payment_create',
                'Registo financeiro criado.',
            );

            return $payment->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Payment $payment, array $data, User $actor): Payment
    {
        return DB::transaction(function () use ($payment, $data): Payment {
            $lockedPayment = Payment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->paymentHasStatus($lockedPayment, PaymentStatus::Paid)) {
                throw ValidationException::withMessages([
                    'payment' => 'Um pagamento confirmado não pode ser alterado; utilize uma operação de reversão própria.',
                ]);
            }

            $lockedPayment->update($this->normalized($data, $lockedPayment));
            $this->auditLogger->record(
                AuditEvents::UPDATE,
                $lockedPayment,
                'payments',
                'legacy_payment_update',
                'Registo financeiro atualizado.',
            );

            return $lockedPayment->refresh();
        });
    }

    public function delete(Payment $payment, User $actor): void
    {
        DB::transaction(function () use ($payment): void {
            $lockedPayment = Payment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->paymentHasStatus($lockedPayment, PaymentStatus::Paid)) {
                throw ValidationException::withMessages([
                    'payment' => 'Um pagamento confirmado não pode ser eliminado; utilize uma operação de reversão própria.',
                ]);
            }

            $this->auditLogger->record(
                AuditEvents::DELETE,
                $lockedPayment,
                'payments',
                'legacy_payment_delete',
                'Registo financeiro eliminado.',
            );
            $lockedPayment->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data, ?Payment $existing = null): array
    {
        $status = $data['status'] instanceof PaymentStatus
            ? $data['status']
            : PaymentStatus::from((string) $data['status']);

        $data['amount'] = DecimalMoney::normalize((string) $data['amount']);
        $data['status'] = $status;
        $data['paid_at'] = $status === PaymentStatus::Paid
            ? ($data['paid_at'] ?? $existing->paid_at ?? now())
            : null;

        return $data;
    }

    private function paymentHasStatus(Payment $payment, PaymentStatus $expected): bool
    {
        $status = $payment->getAttribute('status');

        return $status === $expected || $status === $expected->value;
    }
}
