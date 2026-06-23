<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $leasePayment->payment_number }}</h1></x-slot>
    <div class="mv-card space-y-2"><p>Valor: {{ number_format((float) $leasePayment->amount, 2, ',', '.') }} EUR</p><p>Imputado: {{ number_format((float) $leasePayment->allocated_amount, 2, ',', '.') }} EUR</p><p>Estado: {{ $leasePayment->status->label() }}</p>@if ($leasePayment->receipt)<a class="mv-button-secondary" href="{{ route('candidate.finance.receipts.show', $leasePayment->receipt) }}">Ver comprovativo</a>@endif</div>
</x-app-layout>
