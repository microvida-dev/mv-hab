<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $leasePayment->payment_number }}</h1></x-slot>
    <div class="space-y-6">
        <div class="mv-card space-y-2"><p>Valor: <strong>{{ number_format((float) $leasePayment->amount, 2, ',', '.') }} EUR</strong></p><p>Estado: <strong>{{ $leasePayment->status->label() }}</strong></p><p>Por imputar: <strong>{{ number_format((float) $leasePayment->unallocated_amount, 2, ',', '.') }} EUR</strong></p></div>
        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('backoffice.finance.payments.confirm', $leasePayment) }}">@csrf<button class="mv-button-secondary">Confirmar</button></form>
            <form method="POST" action="{{ route('backoffice.finance.payments.allocate', $leasePayment) }}">@csrf<input type="hidden" name="allocate_oldest" value="1"><button class="mv-button-secondary">Imputar por antiguidade</button></form>
            <form method="POST" action="{{ route('backoffice.finance.receipts.generate', $leasePayment) }}">@csrf<button class="mv-button-secondary">Emitir comprovativo</button></form>
        </div>
    </div>
</x-app-layout>
