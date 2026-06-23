<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $rentInstallment->reference }}</h1></x-slot>
    <div class="mv-card space-y-3">
        <p>Valor: <strong>{{ number_format((float) $rentInstallment->amount_due, 2, ',', '.') }} EUR</strong></p>
        <p>Pago: <strong>{{ number_format((float) $rentInstallment->amount_paid, 2, ',', '.') }} EUR</strong></p>
        <p>Estado: <strong>{{ $rentInstallment->status->label() }}</strong></p>
        <div class="flex gap-3">
            <form method="POST" action="{{ route('backoffice.finance.installments.issue', $rentInstallment) }}">@csrf<button class="mv-button-secondary">Emitir</button></form>
            <form method="POST" action="{{ route('backoffice.finance.installments.waive', $rentInstallment) }}">@csrf<button class="mv-button-secondary">Dispensar</button></form>
        </div>
    </div>
</x-app-layout>
