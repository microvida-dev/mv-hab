<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $regularizationAgreement->agreement_number }}</h1></x-slot>
    <div class="mv-card space-y-2"><p>Total: {{ number_format((float) $regularizationAgreement->total_amount, 2, ',', '.') }} EUR</p><p>Estado: {{ $regularizationAgreement->status->label() }}</p><p>{{ $regularizationAgreement->terms }}</p></div>
</x-app-layout>
