<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $paymentReceipt->receipt_number }}</h1></x-slot>
    <div class="mv-card space-y-3">
        <p>Valor: <strong>{{ number_format((float) $paymentReceipt->total_amount, 2, ',', '.') }} EUR</strong></p>
        <p>Estado: <strong>{{ $paymentReceipt->status->label() }}</strong></p>
        @if ($paymentReceipt->storage_path)<a class="mv-button-secondary" href="{{ route('backoffice.finance.receipts.download', $paymentReceipt) }}">Descarregar</a>@endif
    </div>
</x-app-layout>
