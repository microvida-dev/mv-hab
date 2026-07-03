<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\TenantCommunicationStatus;
use App\Enums\TenantInvoiceStatus;
use App\Enums\TenantPaymentStatus;
use App\Enums\TenantTransitionStatus;
use App\Models\TenantCommunication;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\TenantTransition;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;
use App\Services\Dashboard\Timeline\TimelineEventFactory;

class TenantOperationsTimelineProvider implements TimelineProviderInterface
{

    public function __construct(
        private readonly TimelineEventFactory $factory = new TimelineEventFactory(),
    ) {}

    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('tenant_operations.view')) {
            return [];
        }

        return array_merge(
            $this->transitionEvents(),
            $this->invoiceEvents(),
            $this->paymentEvents(),
            $this->communicationEvents(),
        );
    }

    /** @return array<int, TimelineEvent> */
    private function transitionEvents(): array
    {
        return TenantTransition::query()
            ->with(['tenant', 'housingUnit', 'leaseContract'])
            ->whereIn('status', [
                TenantTransitionStatus::Pending->value,
                TenantTransitionStatus::Ready->value,
                TenantTransitionStatus::Completed->value,
                TenantTransitionStatus::Blocked->value,
            ])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->map(fn (TenantTransition $transition): TimelineEvent => $transition->status === TenantTransitionStatus::Completed
                ? $this->transitionCompletedEvent($transition)
                : $this->transitionPendingEvent($transition)
            )
            ->all();
    }

    /** @return array<int, TimelineEvent> */
    private function invoiceEvents(): array
    {
        return TenantInvoice::query()
            ->with(['tenant', 'housingUnit', 'leaseContract'])
            ->whereIn('status', [
                TenantInvoiceStatus::Issued->value,
                TenantInvoiceStatus::Sent->value,
                TenantInvoiceStatus::PartiallyPaid->value,
                TenantInvoiceStatus::Overdue->value,
                TenantInvoiceStatus::UnderReview->value,
            ])
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->limit(30)
            ->get()
            ->map(fn (TenantInvoice $invoice): TimelineEvent => $invoice->status === TenantInvoiceStatus::Overdue
                ? $this->invoiceOverdueEvent($invoice)
                : $this->invoiceDueEvent($invoice)
            )
            ->all();
    }

    /** @return array<int, TimelineEvent> */
    private function paymentEvents(): array
    {
        return TenantPayment::query()
            ->with(['tenant', 'invoice', 'leaseContract'])
            ->whereIn('status', [
                TenantPaymentStatus::Registered->value,
                TenantPaymentStatus::Confirmed->value,
                TenantPaymentStatus::Reconciled->value,
                TenantPaymentStatus::Partial->value,
            ])
            ->where(function ($query): void {
                $query
                    ->whereNotNull('registered_at')
                    ->orWhereNotNull('confirmed_at')
                    ->orWhereNotNull('reconciled_at');
            })
            ->orderByRaw('COALESCE(confirmed_at, reconciled_at, registered_at, updated_at) asc')
            ->limit(30)
            ->get()
            ->map(fn (TenantPayment $payment): TimelineEvent => $payment->status === TenantPaymentStatus::Registered
                ? $this->paymentRegisteredEvent($payment)
                : $this->paymentConfirmedEvent($payment)
            )
            ->all();
    }

    /** @return array<int, TimelineEvent> */
    private function communicationEvents(): array
    {
        return TenantCommunication::query()
            ->with(['tenant', 'housingUnit', 'leaseContract'])
            ->whereIn('status', [
                TenantCommunicationStatus::Open->value,
                TenantCommunicationStatus::AwaitingMunicipality->value,
                TenantCommunicationStatus::AwaitingTenant->value,
            ])
            ->orderByDesc('last_message_at')
            ->limit(20)
            ->get()
            ->map(fn (TenantCommunication $communication): TimelineEvent => $communication->status === TenantCommunicationStatus::AwaitingMunicipality
                ? $this->communicationAwaitingMunicipalityEvent($communication)
                : $this->communicationOpenEvent($communication)
            )
            ->all();
    }

    private function transitionPendingEvent(TenantTransition $transition): TimelineEvent
    {
        return $this->factory->make(
            id: 'tenant-transition-pending-'.$transition->getKey(),
            type: TimelineType::TenantTransitionPending,
            title: 'Transição para inquilino pendente',
            description: $this->transitionDescription($transition),
            route: route('backoffice.tenant-transitions.index'),
            datetime: $transition->updated_at,
            priority: $transition->status === TenantTransitionStatus::Blocked ? TimelinePriority::High : TimelinePriority::Medium,
            icon: 'user-switch',
            tone: $transition->status === TenantTransitionStatus::Blocked ? 'warning' : 'info',
            workspace: TimelineWorkspace::Tenant,
            metadata: $this->transitionMetadata($transition),
        );
    }

    private function transitionCompletedEvent(TenantTransition $transition): TimelineEvent
    {
        return $this->factory->make(
            id: 'tenant-transition-completed-'.$transition->getKey(),
            type: TimelineType::TenantTransitionCompleted,
            title: 'Transição para inquilino concluída',
            description: $this->transitionDescription($transition),
            route: route('backoffice.tenant-transitions.index'),
            datetime: $transition->completed_at ?? $transition->updated_at,
            priority: TimelinePriority::Low,
            icon: 'check-circle',
            tone: 'success',
            workspace: TimelineWorkspace::Tenant,
            metadata: $this->transitionMetadata($transition),
        );
    }

    private function invoiceDueEvent(TenantInvoice $invoice): TimelineEvent
    {
        return $this->factory->make(
            id: 'tenant-invoice-due-'.$invoice->getKey(),
            type: TimelineType::TenantInvoiceDue,
            title: 'Fatura de inquilino a vencer',
            description: $this->invoiceDescription($invoice),
            route: route('backoffice.tenant-operations.invoices.index'),
            datetime: $invoice->due_date?->startOfDay(),
            priority: $invoice->due_date?->isPast() ? TimelinePriority::High : TimelinePriority::Medium,
            icon: 'invoice',
            tone: $invoice->due_date?->isPast() ? 'warning' : 'info',
            workspace: TimelineWorkspace::Tenant,
            metadata: $this->invoiceMetadata($invoice),
        );
    }

    private function invoiceOverdueEvent(TenantInvoice $invoice): TimelineEvent
    {
        return $this->factory->make(
            id: 'tenant-invoice-overdue-'.$invoice->getKey(),
            type: TimelineType::TenantInvoiceOverdue,
            title: 'Fatura de inquilino vencida',
            description: $this->invoiceDescription($invoice),
            route: route('backoffice.tenant-operations.invoices.index'),
            datetime: $invoice->due_date?->startOfDay(),
            priority: TimelinePriority::Critical,
            icon: 'invoice',
            tone: 'danger',
            workspace: TimelineWorkspace::Tenant,
            metadata: $this->invoiceMetadata($invoice),
        );
    }

    private function paymentRegisteredEvent(TenantPayment $payment): TimelineEvent
    {
        return $this->factory->make(
            id: 'tenant-payment-registered-'.$payment->getKey(),
            type: TimelineType::TenantPaymentRegistered,
            title: 'Pagamento de inquilino registado',
            description: $this->paymentDescription($payment),
            route: route('backoffice.tenant-operations.payments.index'),
            datetime: $payment->registered_at ?? $payment->updated_at,
            priority: TimelinePriority::Medium,
            icon: 'payment',
            tone: 'info',
            workspace: TimelineWorkspace::Tenant,
            metadata: $this->paymentMetadata($payment),
        );
    }

    private function paymentConfirmedEvent(TenantPayment $payment): TimelineEvent
    {
        return $this->factory->make(
            id: 'tenant-payment-confirmed-'.$payment->getKey(),
            type: TimelineType::TenantPaymentConfirmed,
            title: 'Pagamento de inquilino confirmado',
            description: $this->paymentDescription($payment),
            route: route('backoffice.tenant-operations.payments.index'),
            datetime: $payment->confirmed_at ?? $payment->reconciled_at ?? $payment->updated_at,
            priority: TimelinePriority::Low,
            icon: 'payment',
            tone: 'success',
            workspace: TimelineWorkspace::Tenant,
            metadata: $this->paymentMetadata($payment),
        );
    }

    private function communicationOpenEvent(TenantCommunication $communication): TimelineEvent
    {
        return $this->factory->make(
            id: 'tenant-communication-open-'.$communication->getKey(),
            type: TimelineType::TenantCommunicationOpen,
            title: 'Comunicação de inquilino aberta',
            description: $this->communicationDescription($communication),
            route: route('backoffice.tenant-operations.communications.show', $communication),
            datetime: $communication->last_message_at ?? $communication->opened_at ?? $communication->updated_at,
            priority: TimelinePriority::Medium,
            icon: 'message',
            tone: 'info',
            workspace: TimelineWorkspace::Tenant,
            metadata: $this->communicationMetadata($communication),
        );
    }

    private function communicationAwaitingMunicipalityEvent(TenantCommunication $communication): TimelineEvent
    {
        return $this->factory->make(
            id: 'tenant-communication-awaiting-municipality-'.$communication->getKey(),
            type: TimelineType::TenantCommunicationAwaitingMunicipality,
            title: 'Comunicação aguarda resposta do município',
            description: $this->communicationDescription($communication),
            route: route('backoffice.tenant-operations.communications.show', $communication),
            datetime: $communication->last_message_at ?? $communication->opened_at ?? $communication->updated_at,
            priority: TimelinePriority::High,
            icon: 'message-alert',
            tone: 'warning',
            workspace: TimelineWorkspace::Tenant,
            metadata: $this->communicationMetadata($communication),
        );
    }

    private function transitionDescription(TenantTransition $transition): string
    {
        return trim(($transition->tenant?->name ?? 'Inquilino').' · '.($transition->housingUnit?->reference ?? $transition->housingUnit?->code ?? 'Habitação'));
    }

    private function invoiceDescription(TenantInvoice $invoice): string
    {
        return trim(($invoice->invoice_number ?? 'Fatura').' · '.($invoice->tenant?->name ?? 'Inquilino').' · '.$invoice->amount_due.' €');
    }

    private function paymentDescription(TenantPayment $payment): string
    {
        return trim(($payment->payment_number ?? 'Pagamento').' · '.($payment->tenant?->name ?? 'Inquilino').' · '.$payment->amount.' €');
    }

    private function communicationDescription(TenantCommunication $communication): string
    {
        return trim(($communication->subject ?? 'Comunicação').' · '.($communication->tenant?->name ?? 'Inquilino'));
    }

    /** @return array<string, mixed> */
    private function transitionMetadata(TenantTransition $transition): array
    {
        return [
            'tenant_transition_id' => $transition->getKey(),
            'user_id' => $transition->user_id,
            'housing_unit_id' => $transition->housing_unit_id,
            'lease_contract_id' => $transition->lease_contract_id,
            'status' => $transition->status?->value ?? $transition->status,
        ];
    }

    /** @return array<string, mixed> */
    private function invoiceMetadata(TenantInvoice $invoice): array
    {
        return [
            'tenant_invoice_id' => $invoice->getKey(),
            'invoice_number' => $invoice->invoice_number,
            'user_id' => $invoice->user_id,
            'lease_contract_id' => $invoice->lease_contract_id,
            'status' => $invoice->status?->value ?? $invoice->status,
            'amount_due' => $invoice->amount_due,
            'due_date' => $invoice->due_date?->toDateString(),
        ];
    }

    /** @return array<string, mixed> */
    private function paymentMetadata(TenantPayment $payment): array
    {
        return [
            'tenant_payment_id' => $payment->getKey(),
            'payment_number' => $payment->payment_number,
            'user_id' => $payment->user_id,
            'lease_contract_id' => $payment->lease_contract_id,
            'status' => $payment->status?->value ?? $payment->status,
            'amount' => $payment->amount,
        ];
    }

    /** @return array<string, mixed> */
    private function communicationMetadata(TenantCommunication $communication): array
    {
        return [
            'tenant_communication_id' => $communication->getKey(),
            'subject' => $communication->subject,
            'user_id' => $communication->user_id,
            'lease_contract_id' => $communication->lease_contract_id,
            'status' => $communication->status?->value ?? $communication->status,
        ];
    }
}
