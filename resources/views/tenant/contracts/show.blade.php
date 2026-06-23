<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Contrato {{ $contract->contract_number }}</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mv-card">
            <dl class="grid gap-4 md:grid-cols-3">
                <div><dt class="text-xs text-ink-500">Estado</dt><dd class="font-semibold">{{ $contract->status?->label() ?? $contract->status }}</dd></div>
                <div><dt class="text-xs text-ink-500">Renda mensal</dt><dd class="font-semibold">{{ number_format((float) $contract->monthly_rent, 2, ',', '.') }} EUR</dd></div>
                <div><dt class="text-xs text-ink-500">Habitação</dt><dd class="font-semibold">{{ $contract->housingUnit?->code }}</dd></div>
            </dl>
        </div>
        <div class="mv-card">
            <p class="text-sm text-ink-600">{{ $contract->housingUnit?->address ?? 'Morada não registada.' }}</p>
        </div>
    </div>
</x-app-layout>
