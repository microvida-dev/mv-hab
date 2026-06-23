<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-ink-900">Área do inquilino</h1>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="mv-card"><p class="text-xs text-ink-500">Contratos ativos</p><p class="text-2xl font-semibold">{{ $summary['contracts'] }}</p></div>
            <div class="mv-card"><p class="text-xs text-ink-500">Faturas em aberto</p><p class="text-2xl font-semibold">{{ $summary['open_invoices'] }}</p></div>
            <div class="mv-card"><p class="text-xs text-ink-500">Valor em aberto</p><p class="text-2xl font-semibold">{{ number_format((float) $summary['amount_outstanding'], 2, ',', '.') }} EUR</p></div>
            <div class="mv-card"><p class="text-xs text-ink-500">Pedidos manutenção</p><p class="text-2xl font-semibold">{{ $summary['maintenance_open'] }}</p></div>
            <div class="mv-card"><p class="text-xs text-ink-500">Vistorias agendadas</p><p class="text-2xl font-semibold">{{ $summary['scheduled_inspections'] }}</p></div>
            <div class="mv-card"><p class="text-xs text-ink-500">Comunicações abertas</p><p class="text-2xl font-semibold">{{ $summary['open_communications'] }}</p></div>
        </div>

        <div class="mv-card">
            <p class="text-sm text-ink-600">Os valores apresentados refletem a informação registada na plataforma. Em caso de divergência, prevalece a validação dos serviços municipais competentes.</p>
        </div>

        <div class="grid gap-3 md:grid-cols-4">
            <a class="mv-button-primary" href="{{ route('tenant.invoices.index') }}">Faturas</a>
            <a class="mv-button-secondary" href="{{ route('tenant.payments.index') }}">Pagamentos</a>
            <a class="mv-button-secondary" href="{{ route('tenant.maintenance.index') }}">Manutenção</a>
            <a class="mv-button-secondary" href="{{ route('tenant.communications.index') }}">Comunicações</a>
        </div>

        <div class="grid gap-4">
            @forelse ($contracts as $contract)
                <a class="mv-card block" href="{{ route('tenant.contracts.show', $contract) }}">
                    <p class="font-semibold text-ink-900">{{ $contract->contract_number ?? 'Contrato '.$contract->id }}</p>
                    <p class="text-sm text-ink-500">{{ $contract->housingUnit?->address ?? 'Habitação sem morada registada' }}</p>
                </a>
            @empty
                <div class="mv-card"><p class="text-sm text-ink-600">Não existem contratos pós-atribuição ativos para apresentar.</p></div>
            @endforelse
        </div>
    </div>
</x-app-layout>
