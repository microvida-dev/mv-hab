<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Contratos</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        @forelse ($contracts as $contract)
            <a class="mv-card block" href="{{ route('tenant.contracts.show', $contract) }}">
                <p class="font-semibold">{{ $contract->contract_number ?? 'Contrato '.$contract->id }}</p>
                <p class="text-sm text-ink-500">{{ $contract->housingUnit?->address }} · {{ number_format((float) $contract->monthly_rent, 2, ',', '.') }} EUR/mês</p>
            </a>
        @empty
            <div class="mv-card"><p class="text-sm text-ink-600">Sem contratos ativos.</p></div>
        @endforelse
        {{ $contracts->links() }}
    </div>
</x-app-layout>
