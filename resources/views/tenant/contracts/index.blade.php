<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Área do Inquilino"
            title="Contratos"
            description="Consulte os contratos ativos associados à sua área de inquilino."
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.section padding="p-0" class="overflow-hidden">
            @forelse ($contracts as $contract)
                <a class="block border-b border-ink-100 p-4 transition hover:bg-ink-50" href="{{ route('tenant.contracts.show', $contract) }}">
                    <p class="font-semibold text-ink-900">{{ $contract->contract_number ?? 'Contrato '.$contract->id }}</p>
                    <p class="mt-1 text-sm text-ink-500">{{ $contract->housingUnit?->address }} · {{ number_format((float) $contract->monthly_rent, 2, ',', '.') }} EUR/mês</p>
                </a>
            @empty
                <x-mv.alert>Sem contratos ativos.</x-mv.alert>
            @endforelse
        </x-mv.section>

        {{ $contracts->links() }}
    </div>
</x-app-layout>
