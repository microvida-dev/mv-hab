<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $rentInstallment->reference }}</h1></x-slot>
    <div class="mv-card space-y-2"><p>Vencimento: {{ $rentInstallment->due_date?->format('d/m/Y') }}</p><p>Valor: {{ number_format((float) $rentInstallment->amount_due, 2, ',', '.') }} EUR</p><p>Estado: {{ $rentInstallment->status->label() }}</p></div>
</x-app-layout>
