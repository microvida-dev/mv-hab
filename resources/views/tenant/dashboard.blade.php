<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Área do Inquilino"
            title="Painel do inquilino"
            description="Consulte contratos, rendas, pagamentos, manutenção, vistorias e comunicações."
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="grid gap-4 md:grid-cols-3">
            <x-mv.stat-card label="Contratos ativos" :value="$summary['contracts']" />
            <x-mv.stat-card label="Faturas em aberto" :value="$summary['open_invoices']" />
            <x-mv.stat-card label="Valor em aberto" :value="number_format((float) $summary['amount_outstanding'], 2, ',', '.').' EUR'" />
            <x-mv.stat-card label="Pedidos manutenção" :value="$summary['maintenance_open']" />
            <x-mv.stat-card label="Vistorias agendadas" :value="$summary['scheduled_inspections']" />
            <x-mv.stat-card label="Comunicações abertas" :value="$summary['open_communications']" />
        </div>

        <x-mv.alert>
            Os valores apresentados refletem a informação registada na plataforma. Em caso de divergência, prevalece a validação dos serviços municipais competentes.
        </x-mv.alert>

        <div class="grid gap-3 md:grid-cols-4">
            <a class="mv-button-primary justify-center" href="{{ route('tenant.invoices.index') }}">Faturas</a>
            <a class="mv-button-secondary justify-center" href="{{ route('tenant.payments.index') }}">Pagamentos</a>
            <a class="mv-button-secondary justify-center" href="{{ route('tenant.maintenance.index') }}">Manutenção</a>
            <a class="mv-button-secondary justify-center" href="{{ route('tenant.communications.index') }}">Comunicações</a>
        </div>

        <x-mv.section title="Contratos ativos">
            <div class="grid gap-4">
                @forelse ($contracts as $contract)
                    <a class="block rounded-2xl border border-ink-100 p-4 transition hover:border-mvhab-primary/30 hover:bg-mvhab-surface" href="{{ route('tenant.contracts.show', $contract) }}">
                        <p class="font-semibold text-ink-900">{{ $contract->contract_number ?? 'Contrato '.$contract->id }}</p>
                        <p class="mt-1 text-sm text-ink-500">{{ $contract->housingUnit?->address ?? 'Habitação sem morada registada' }}</p>
                    </a>
                @empty
                    <x-mv.alert>Não existem contratos pós-atribuição ativos para apresentar.</x-mv.alert>
                @endforelse
            </div>
        </x-mv.section>
    </div>
</x-app-layout>
