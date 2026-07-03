<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\LeasePaymentStatus;
use App\Enums\RentInstallmentStatus;
use App\Models\LeasePayment;
use App\Models\RentInstallment;
use App\Models\User;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineEventFactory;

class RentTimelineProvider extends BaseTimelineProvider
{
    public function __construct(
        private readonly TimelineEventFactory $factory = new TimelineEventFactory(),
    ) {}

    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('finance.view')) {
            return [];
        }

        return array_merge(
            $this->installmentEvents(),
            $this->paymentEvents(),
        );
    }

    /**
     * @return array<int, TimelineEvent>
     */
    private function installmentEvents(): array
    {
        return RentInstallment::query()
            ->with(['tenant', 'leaseContract'])
            ->whereIn('status', [
                RentInstallmentStatus::Scheduled->value,
                RentInstallmentStatus::Issued->value,
                RentInstallmentStatus::PartiallyPaid->value,
                RentInstallmentStatus::Overdue->value,
                RentInstallmentStatus::UnderAgreement->value,
            ])
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->limit(30)
            ->get()
            ->map(fn (RentInstallment $installment): TimelineEvent => $installment->status === RentInstallmentStatus::Overdue
                ? $this->overdueEvent($installment)
                : $this->dueEvent($installment)
            )
            ->all();
    }

    /**
     * @return array<int, TimelineEvent>
     */
    private function paymentEvents(): array
    {
        return LeasePayment::query()
            ->with(['tenant', 'leaseContract'])
            ->whereIn('status', [
                LeasePaymentStatus::Confirmed->value,
                LeasePaymentStatus::Allocated->value,
                LeasePaymentStatus::PartiallyAllocated->value,
            ])
            ->whereNotNull('received_at')
            ->orderBy('received_at')
            ->limit(20)
            ->get()
            ->map(fn (LeasePayment $payment): TimelineEvent => $this->paymentReceivedEvent($payment))
            ->all();
    }

    private function dueEvent(RentInstallment $installment): TimelineEvent
    {
        return $this->factory->make(
            id: 'rent-due-'.$installment->getKey(),
            type: TimelineType::RentDue,
            title: 'Prestação de renda a vencer',
            description: $this->installmentDescription($installment),
            route: route('backoffice.finance.installments.index'),
            datetime: $installment->due_date?->startOfDay(),
            priority: $installment->due_date?->isPast()
                ? TimelinePriority::High
                : TimelinePriority::Medium,
            icon: 'payment',
            tone: $installment->due_date?->isPast() ? 'warning' : 'info',
            workspace: TimelineWorkspace::Finance,
            metadata: $this->installmentMetadata($installment),
        );
    }

    private function overdueEvent(RentInstallment $installment): TimelineEvent
    {
        return $this->factory->make(
            id: 'rent-overdue-'.$installment->getKey(),
            type: TimelineType::RentOverdue,
            title: 'Prestação de renda em atraso',
            description: $this->installmentDescription($installment),
            route: route('backoffice.finance.installments.index'),
            datetime: $installment->overdue_at ?? $installment->due_date?->startOfDay(),
            priority: TimelinePriority::Critical,
            icon: 'payment',
            tone: 'danger',
            workspace: TimelineWorkspace::Finance,
            metadata: $this->installmentMetadata($installment),
        );
    }

    private function paymentReceivedEvent(LeasePayment $payment): TimelineEvent
    {
        return $this->factory->make(
            id: 'lease-payment-received-'.$payment->getKey(),
            type: TimelineType::LeasePaymentReceived,
            title: 'Pagamento de renda recebido',
            description: $this->paymentDescription($payment),
            route: route('backoffice.finance.payments.index'),
            datetime: $payment->received_at,
            priority: TimelinePriority::Low,
            icon: 'payment',
            tone: 'success',
            workspace: TimelineWorkspace::Finance,
            metadata: $this->paymentMetadata($payment),
        );
    }

    private function installmentDescription(RentInstallment $installment): string
    {
        $tenant = $installment->tenant?->name ?? 'Inquilino';
        $reference = $installment->reference ?? 'Prestação';

        return trim("{$reference} · {$tenant} · {$installment->amount_due} €");
    }

    private function paymentDescription(LeasePayment $payment): string
    {
        $tenant = $payment->tenant?->name ?? 'Inquilino';
        $reference = $payment->payment_number ?? 'Pagamento';

        return trim("{$reference} · {$tenant} · {$payment->amount} €");
    }

    /**
     * @return array<string, mixed>
     */
    private function installmentMetadata(RentInstallment $installment): array
    {
        return [
            'rent_installment_id' => $installment->getKey(),
            'reference' => $installment->reference,
            'tenant_id' => $installment->user_id,
            'tenant_name' => $installment->tenant?->name,
            'lease_contract_id' => $installment->lease_contract_id,
            'status' => $installment->status?->value ?? $installment->status,
            'amount_due' => $installment->amount_due,
            'amount_paid' => $installment->amount_paid,
            'amount_outstanding' => $installment->amount_outstanding,
            'due_date' => $installment->due_date?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentMetadata(LeasePayment $payment): array
    {
        return [
            'lease_payment_id' => $payment->getKey(),
            'payment_number' => $payment->payment_number,
            'tenant_id' => $payment->user_id,
            'tenant_name' => $payment->tenant?->name,
            'lease_contract_id' => $payment->lease_contract_id,
            'status' => $payment->status?->value ?? $payment->status,
            'amount' => $payment->amount,
            'received_at' => $payment->received_at?->toIso8601String(),
        ];
    }
}
